<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use App\Services\AuditLogger;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['all', 'pending', 'approved', 'rejected'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $reviews = Review::query()
            ->with(['user', 'product'])
            ->when(
                isset($validated['status']) && $validated['status'] !== 'all',
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 50));

        return ReviewResource::collection($reviews);
    }

    public function show(Review $review)
    {
        Gate::authorize('viewAny', Order::class);

        return ReviewResource::make($review->load(['user', 'product']));
    }

    public function update(ModerateReviewRequest $request, Review $review, ReviewService $service, AuditLogger $audit): ReviewResource
    {
        Gate::authorize('viewAny', Order::class);

        $previousStatus = $review->status;
        $moderated = $service->moderate($review, $request->validated('decision'));

        $audit->log('review.moderated', $request->user(), $moderated, [
            'product_id' => $moderated->product?->external_id,
            'previous_status' => $previousStatus,
            'new_status' => $moderated->status,
        ]);

        return ReviewResource::make($moderated);
    }
}
