<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductReviewController extends Controller
{
    /**
     * Public listing of approved reviews for an active product.
     */
    public function index(Request $request, Product $product, ReviewService $service)
    {
        abort_unless($product->is_active, 404);

        return ReviewResource::collection(
            $service->approvedList($product, (int) $request->query('per_page', 10))
        );
    }

    /**
     * Public aggregate over approved reviews only.
     */
    public function summary(Product $product, ReviewService $service): JsonResponse
    {
        abort_unless($product->is_active, 404);

        return response()->json(['data' => $service->summary($product)]);
    }

    /**
     * The authenticated customer's own review for a product, if any.
     */
    public function mine(Request $request, Product $product, ReviewService $service): ReviewResource
    {
        $review = $service->ownReview($request->user(), $product);
        abort_if($review === null, 404);

        return ReviewResource::make($review);
    }

    /**
     * Whether the authenticated customer may currently submit a review.
     */
    public function eligibility(Request $request, Product $product, ReviewService $service): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $state = $service->eligibility($request->user(), $product);

        return response()->json(['data' => [
            'eligible' => $state['eligible'],
            'reason' => $state['reason'],
            'hasReviewed' => $state['has_reviewed'],
            'verifiedPurchase' => $state['eligible'] || $state['has_reviewed'],
        ]]);
    }

    public function store(StoreReviewRequest $request, Product $product, ReviewService $service): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $review = $service->create($request->user(), $product, $request->validated());

        return response()->json(['data' => ReviewResource::make($review)], 201);
    }

    public function update(UpdateReviewRequest $request, Review $review, ReviewService $service): ReviewResource
    {
        Gate::authorize('update', $review);

        return ReviewResource::make($service->update($review, $request->validated()));
    }

    public function destroy(Request $request, Review $review, ReviewService $service): JsonResponse
    {
        Gate::authorize('delete', $review);

        $service->delete($review);

        return response()->json(['message' => 'Review deleted.']);
    }
}
