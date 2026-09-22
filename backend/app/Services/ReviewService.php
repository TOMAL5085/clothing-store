<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReviewService
{
    /**
     * Eligibility snapshot used by the store endpoint and the public
     * eligibility endpoint. Never throws: guests are rejected by auth
     * middleware before this is reached.
     *
     * @return array{eligible:bool, reason:?string, has_reviewed:bool, purchased:bool}
     */
    public function eligibility(User $user, Product $product): array
    {
        if ($this->hasReview($user, $product)) {
            return [
                'eligible' => false,
                'reason' => 'You have already reviewed this product.',
                'has_reviewed' => true,
                'purchased' => true,
            ];
        }

        $purchased = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->exists();

        if (! $purchased) {
            return [
                'eligible' => false,
                'reason' => 'Only customers who purchased this product can review it.',
                'has_reviewed' => false,
                'purchased' => false,
            ];
        }

        $delivered = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'delivered')
                ->where('payment_status', 'paid'))
            ->exists();

        if (! $delivered) {
            return [
                'eligible' => false,
                'reason' => 'You can review this product once your order is delivered.',
                'has_reviewed' => false,
                'purchased' => true,
            ];
        }

        return ['eligible' => true, 'reason' => null, 'has_reviewed' => false, 'purchased' => true];
    }

    public function hasReview(User $user, Product $product): bool
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function ownReview(User $user, Product $product): ?Review
    {
        return Review::query()
            ->with(['user', 'product'])
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();
    }

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function approvedList(Product $product, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->with(['user', 'product'])
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Public aggregate over approved reviews only.
     *
     * @return array{count:int, average:float, distribution:array<int, int>}
     */
    public function summary(Product $product): array
    {
        $rows = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];
        $count = 0;
        $sum = 0;
        foreach ([1, 2, 3, 4, 5] as $stars) {
            $total = (int) ($rows[$stars] ?? 0);
            $distribution[$stars] = $total;
            $count += $total;
            $sum += $stars * $total;
        }

        return [
            'count' => $count,
            'average' => $count > 0 ? round($sum / $count, 2) : 0.0,
            'distribution' => $distribution,
        ];
    }

    /**
     * @param  array{rating:int, title?:?string, body:string}  $data
     */
    public function create(User $user, Product $product, array $data): Review
    {
        $state = $this->eligibility($user, $product);

        if ($state['has_reviewed']) {
            throw ValidationException::withMessages(['review' => $state['reason']]);
        }

        if (! $state['eligible']) {
            if (! $state['purchased']) {
                throw new AccessDeniedHttpException((string) $state['reason']);
            }

            throw ValidationException::withMessages(['review' => $state['reason']]);
        }

        try {
            $review = DB::transaction(function () use ($user, $product, $data) {
                // Server-managed fields are assigned directly (never mass-assigned
                // from request input) alongside the validated payload.
                $review = new Review([
                    'rating' => $data['rating'],
                    'title' => $data['title'] ?? null,
                    'body' => $data['body'],
                ]);
                $review->user()->associate($user);
                $review->product()->associate($product);
                $review->status = 'pending';
                $review->verified_purchase = true;
                $review->save();

                return $review;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                throw ValidationException::withMessages(['review' => 'You have already reviewed this product.']);
            }

            throw $e;
        }

        return $review->load(['user', 'product']);
    }

    /**
     * @param  array{rating?:int, title?:?string, body?:string}  $data
     */
    public function update(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $payload = array_intersect_key($data, ['rating' => true, 'title' => true, 'body' => true]);
            $review->fill($payload);

            // Editing an approved review sends it back through moderation so the
            // public listing can never be bait-and-switched.
            if ($review->isApproved()) {
                $review->status = 'pending';
            }

            $review->save();
            $this->refreshProductAggregates($review->product);

            return $review->fresh(['user', 'product']);
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $product = $review->product;
            $review->delete();
            $this->refreshProductAggregates($product);
        });
    }

    public function moderate(Review $review, string $decision): Review
    {
        return DB::transaction(function () use ($review, $decision) {
            $review->status = $decision;
            $review->save();
            $this->refreshProductAggregates($review->product);

            return $review->fresh(['user', 'product']);
        });
    }

    /**
     * Keep the denormalized product columns in sync with approved reviews so
     * listings, sorting and the product resource reflect moderation.
     */
    public function refreshProductAggregates(Product $product): void
    {
        $aggregate = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
            ->first();

        $product->update([
            'reviews_count' => (int) ($aggregate->review_count ?? 0),
            'rating' => $aggregate->average_rating !== null ? round((float) $aggregate->average_rating, 2) : 0,
        ]);
    }
}
