<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsRequest;
use App\Models\Order;
use App\Services\Analytics\AnalyticsRange;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    public function overview(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $range = AnalyticsRange::fromInput($request->rangeInput());
        $current = $analytics->paidOrdersAggregate($range);
        $previous = $analytics->paidOrdersAggregate($range->previous());

        return response()->json([
            'data' => $analytics->overview($range, $current, $previous, $analytics->refundedAggregate($range)),
        ]);
    }

    public function sales(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $range = AnalyticsRange::fromInput($request->rangeInput());

        return response()->json(['data' => $analytics->salesSeries($range)]);
    }

    public function orders(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $range = AnalyticsRange::fromInput($request->rangeInput());

        return response()->json(['data' => $analytics->orderBreakdown($range)]);
    }

    public function products(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validated();
        $range = AnalyticsRange::fromInput($request->rangeInput());

        return response()->json(['data' => [
            'range' => $range->metadata(),
            'items' => $analytics->topProducts($range, (int) ($validated['limit'] ?? 10), $validated['sort'] ?? 'revenue'),
        ]]);
    }

    public function categories(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validated();
        $range = AnalyticsRange::fromInput($request->rangeInput());

        return response()->json(['data' => [
            'range' => $range->metadata(),
            'items' => $analytics->topCategories($range, (int) ($validated['limit'] ?? 10)),
        ]]);
    }

    public function customers(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $range = AnalyticsRange::fromInput($request->rangeInput());

        return response()->json(['data' => [
            'range' => $range->metadata(),
            ...$analytics->customerMetrics($range),
        ]]);
    }

    public function inventory(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validated();

        return response()->json(['data' => [
            ...$analytics->inventoryMetrics(),
            'low_stock_list' => $analytics->lowStockList((int) ($validated['limit'] ?? 20)),
        ]]);
    }

    public function reviews(AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        return response()->json(['data' => $analytics->reviewMetrics()]);
    }

    public function wishlist(AnalyticsRequest $request, AnalyticsService $analytics)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validated();

        return response()->json(['data' => $analytics->wishlistMetrics((int) ($validated['limit'] ?? 10))]);
    }
}
