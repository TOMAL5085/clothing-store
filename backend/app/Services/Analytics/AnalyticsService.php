<?php

namespace App\Services\Analytics;

use App\Models\MarketingEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Financial definitions (see CONTEXT.md Phase 7):
     * - Paid population: orders with payment_status = 'paid'.
     * - Gross revenue: SUM(orders.total) over the paid population in range.
     * - Refunded amount: SUM(refunds.amount) for status = 'succeeded' with
     *   requested_at in range. Refunds only exist for paid orders per the
     *   Phase 4 resolution model, so every succeeded refund offsets revenue.
     * - Net revenue: gross minus refunded.
     * - AOV: gross revenue divided by paid order count.
     * Money stays in major units with two decimals, matching OrderResource.
     */

    /**
     * @return array{orders:int, gross:float}
     */
    public function paidOrdersAggregate(AnalyticsRange $range): array
    {
        $row = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as gross')
            ->first();

        return [
            'orders' => (int) ($row->orders ?? 0),
            'gross' => round((float) ($row->gross ?? 0), 2),
        ];
    }

    public function refundedAggregate(AnalyticsRange $range): float
    {
        $total = Refund::query()
            ->where('status', 'succeeded')
            ->whereBetween('requested_at', [$range->from, $range->to])
            ->sum('amount');

        return round((float) $total, 2);
    }

    /**
     * @param  array{orders:int, gross:float}  $current
     * @param  array{orders:int, gross:float}  $previous
     */
    public function overview(AnalyticsRange $range, array $current, array $previous, float $refunded): array
    {
        $revenue = round($current['gross'] - $refunded, 2);
        $previousRefunded = $this->refundedAggregate($range->previous());
        $previousRevenue = round($previous['gross'] - $previousRefunded, 2);

        $totalOrders = $this->countOrdersInRange($range);
        $previousTotalOrders = $this->countOrdersInRange($range->previous());
        $inventory = $this->inventoryMetrics();

        return [
            'range' => $range->metadata(),
            'revenue' => $this->compared($revenue, $previousRevenue),
            'gross_revenue' => $current['gross'],
            'refunded_amount' => $refunded,
            'orders' => $this->compared($totalOrders, $previousTotalOrders),
            'paid_orders' => $this->compared($current['orders'], $previous['orders']),
            'average_order_value' => $this->compared(
                $this->averageOrderValue($current),
                $this->averageOrderValue($previous)
            ),
            'customers' => $this->customerMetrics($range),
            'products' => [
                'active' => $inventory['active_products'],
                'low_stock' => $inventory['low_stock_products'],
                'out_of_stock' => $inventory['out_of_stock_products'],
            ],
            'pending_reviews' => Review::query()->where('status', 'pending')->count(),
            'wishlist_items' => WishlistItem::query()->count(),
        ];
    }

    /**
     * @param  array{orders:int, gross:float}  $aggregate
     */
    public function averageOrderValue(array $aggregate): float
    {
        if ($aggregate['orders'] === 0) {
            return 0.0;
        }

        return round($aggregate['gross'] / $aggregate['orders'], 2);
    }

    /**
     * @return array{value:float|int, previous:float|int|null, change_percent:float|null}
     */
    public function compared(float|int $value, float|int $previous): array
    {
        if ($previous == 0) {
            return ['value' => $value, 'previous' => $previous, 'change_percent' => null];
        }

        return [
            'value' => $value,
            'previous' => $previous,
            'change_percent' => round((($value - $previous) / $previous) * 100, 1),
        ];
    }

    public function countOrdersInRange(AnalyticsRange $range): int
    {
        return Order::query()
            ->whereBetween('created_at', [$range->from, $range->to])
            ->count();
    }

    /**
     * Chronological per-bucket paid orders, gross and net revenue with gaps filled.
     *
     * @return array{range:array<string, mixed>, series:list<array{period:string, orders:int, gross_revenue:float, refunded_amount:float, revenue:float}>}
     */
    public function salesSeries(AnalyticsRange $range): array
    {
        $bucket = $range->bucketExpression('orders.created_at');

        $orderBuckets = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('orders.created_at', [$range->from, $range->to])
            ->selectRaw("{$bucket} as period, COUNT(*) as orders, COALESCE(SUM(orders.total), 0) as gross")
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $refunds = Refund::query()
            ->where('status', 'succeeded')
            ->whereBetween('requested_at', [$range->from, $range->to])
            ->selectRaw($range->bucketExpression('requested_at').' as period, COALESCE(SUM(amount), 0) as refunded')
            ->groupBy('period')
            ->pluck('refunded', 'period');

        $series = [];
        foreach ($range->buckets() as $period) {
            $bucketRow = $orderBuckets[$period] ?? null;
            $gross = round((float) ($bucketRow->gross ?? 0), 2);
            $refunded = round((float) ($refunds[$period] ?? 0), 2);
            $series[] = [
                'period' => $period,
                'orders' => (int) ($bucketRow->orders ?? 0),
                'gross_revenue' => $gross,
                'refunded_amount' => $refunded,
                'revenue' => round($gross - $refunded, 2),
            ];
        }

        return ['range' => $range->metadata(), 'series' => $series];
    }

    /**
     * @return array{total:int, by_status:list<array{status:string, count:int}>, by_payment_status:list<array{status:string, count:int}>, refunded_orders:int}
     */
    public function orderBreakdown(AnalyticsRange $range): array
    {
        $statusCounts = Order::query()
            ->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $paymentCounts = Order::query()
            ->whereBetween('created_at', [$range->from, $range->to])
            ->selectRaw('payment_status, COUNT(*) as total')
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status');

        $refundedOrders = Order::query()
            ->whereBetween('orders.created_at', [$range->from, $range->to])
            ->whereExists(function ($query) use ($range) {
                $query->select(DB::raw(1))
                    ->from('refunds')
                    ->whereColumn('refunds.order_id', 'orders.id')
                    ->where('refunds.status', 'succeeded')
                    ->whereBetween('refunds.requested_at', [$range->from, $range->to]);
            })
            ->count();

        return [
            'range' => $range->metadata(),
            'total' => (int) $statusCounts->sum(),
            'by_status' => collect(Order::STATUSES)
                ->map(fn (string $status) => ['status' => $status, 'count' => (int) ($statusCounts[$status] ?? 0)])
                ->values()
                ->all(),
            'by_payment_status' => collect(Order::PAYMENT_STATUSES)
                ->map(fn (string $status) => ['status' => $status, 'count' => (int) ($paymentCounts[$status] ?? 0)])
                ->values()
                ->all(),
            'refunded_orders' => $refundedOrders,
        ];
    }

    /**
     * Product sales from order_items of paid orders in range. Single query.
     *
     * @return list<array<string, mixed>>
     */
    public function topProducts(AnalyticsRange $range, int $limit, string $sort): array
    {
        $direction = 'desc';

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$range->from, $range->to])
            ->selectRaw('order_items.product_id')
            ->selectRaw('MAX(products.external_id) as product_id_external')
            ->selectRaw('MAX(products.slug) as slug')
            ->selectRaw('MAX(products.name) as name')
            ->selectRaw('MAX(products.is_active::int) as is_active')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('COALESCE(SUM(order_items.line_total), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->groupBy('order_items.product_id')
            ->orderBy($sort === 'quantity' ? 'units_sold' : 'revenue', $direction)
            ->orderBy('order_items.product_id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id_external,
                'slug' => $row->slug,
                'name' => $row->name,
                'is_active' => (bool) $row->is_active,
                'units_sold' => (int) $row->units_sold,
                'revenue' => round((float) $row->revenue, 2),
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topCategories(AnalyticsRange $range, int $limit): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$range->from, $range->to])
            ->selectRaw('categories.id as category_id')
            ->selectRaw('MAX(categories.slug) as slug')
            ->selectRaw('MAX(categories.label) as label')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('COALESCE(SUM(order_items.line_total), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->groupBy('categories.id')
            ->orderByDesc('revenue')
            ->orderBy('categories.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'slug' => $row->slug,
                'label' => $row->label,
                'units_sold' => (int) $row->units_sold,
                'revenue' => round((float) $row->revenue, 2),
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    /**
     * Aggregate-only customer metrics; no personal data leaves the database.
     *
     * @return array{total_customers:int, purchasing_customers:int, new_customers:int, repeat_customers:int, guest_orders:int, authenticated_orders:int}
     */
    public function customerMetrics(AnalyticsRange $range): array
    {
        $total = User::query()->where('role', 'customer')->count();

        $purchasing = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');

        $new = User::query()
            ->where('role', 'customer')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->count();

        $repeat = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('user_id')
            ->selectRaw('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $guest = Order::query()
            ->where('payment_status', 'paid')
            ->whereNull('user_id')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->count();

        $authenticated = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$range->from, $range->to])
            ->count();

        return [
            'total_customers' => $total,
            'purchasing_customers' => $purchasing,
            'new_customers' => $new,
            'repeat_customers' => $repeat,
            'guest_orders' => $guest,
            'authenticated_orders' => $authenticated,
        ];
    }

    /**
     * Uses the exact Product::getInventoryStatusAttribute rule in SQL:
     * out of stock when not in_stock or quantity <= 0, otherwise low stock
     * when quantity is at or below the per-product threshold (default 5).
     *
     * @return array{active_products:int, out_of_stock_products:int, low_stock_products:int, total_units:int}
     */
    public function inventoryMetrics(): array
    {
        $active = Product::query()->where('is_active', true);

        return [
            'active_products' => (clone $active)->count(),
            'out_of_stock_products' => (clone $active)
                ->where(fn ($query) => $query->where('in_stock', false)->orWhere('stock_quantity', '<=', 0))
                ->count(),
            'low_stock_products' => (clone $active)
                ->where('in_stock', true)
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
            'total_units' => (int) ((clone $active)->sum('stock_quantity')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lowStockList(int $limit): array
    {
        return Product::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where(fn ($out) => $out->where('in_stock', false)->orWhere('stock_quantity', '<=', 0))
                ->orWhere(fn ($low) => $low
                    ->where('in_stock', true)
                    ->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')))
            ->orderBy('stock_quantity')
            ->orderBy('id')
            ->limit($limit)
            ->get(['external_id', 'slug', 'name', 'stock_quantity', 'low_stock_threshold', 'in_stock'])
            ->map(fn (Product $product) => [
                'product_id' => $product->external_id,
                'slug' => $product->slug,
                'name' => $product->name,
                'stock_quantity' => (int) $product->stock_quantity,
                'low_stock_threshold' => (int) $product->low_stock_threshold,
                'inventory_status' => $product->inventory_status,
            ])
            ->all();
    }

    /**
     * @return array{total:int, pending:int, approved:int, rejected:int, average_approved_rating:float, reviewed_products:int}
     */
    public function reviewMetrics(): array
    {
        $counts = Review::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $average = Review::query()
            ->where('status', 'approved')
            ->avg('rating');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'average_approved_rating' => $average === null ? 0.0 : round((float) $average, 2),
            'reviewed_products' => Review::query()
                ->where('status', 'approved')
                ->distinct()
                ->count('product_id'),
        ];
    }

    /**
     * @return array{total_items:int, wishlists_with_account:int, guest_wishlists:int, top_products:list<array<string, mixed>>}
     */
    public function wishlistMetrics(int $limit): array
    {
        $top = WishlistItem::query()
            ->join('products', 'products.id', '=', 'wishlist_items.product_id')
            ->where('products.is_active', true)
            ->selectRaw('wishlist_items.product_id')
            ->selectRaw('MAX(products.external_id) as product_id_external')
            ->selectRaw('MAX(products.slug) as slug')
            ->selectRaw('MAX(products.name) as name')
            ->selectRaw('COUNT(*) as saves')
            ->groupBy('wishlist_items.product_id')
            ->orderByDesc('saves')
            ->orderBy('wishlist_items.product_id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id_external,
                'slug' => $row->slug,
                'name' => $row->name,
                'saves' => (int) $row->saves,
            ])
            ->all();

        return [
            'total_items' => WishlistItem::query()->count(),
            'wishlists_with_account' => Wishlist::query()->whereNotNull('user_id')->count(),
            'guest_wishlists' => Wishlist::query()->whereNull('user_id')->count(),
            'top_products' => $top,
        ];
    }

    /**
     * Conversion funnel from first-party tracked events. These are
     * behavioral measurements only — financial reporting stays with the
     * order-of-record metrics above and is never overridden by this data.
     *
     * @return array<string, mixed>
     */
    public function marketingFunnel(AnalyticsRange $range): array
    {
        $counts = MarketingEvent::query()
            ->whereBetween('occurred_at', [$range->from, $range->to])
            ->selectRaw('event_name, COUNT(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name');

        $get = fn (string $name): int => (int) ($counts[$name] ?? 0);

        $productViews = $get('product_view');
        $addToCarts = $get('add_to_cart');
        $beginCheckouts = $get('begin_checkout');
        $purchases = $get('purchase');

        $attributed = MarketingEvent::query()
            ->where('event_name', 'purchase')
            ->whereBetween('occurred_at', [$range->from, $range->to])
            ->whereNotNull('attribution_id')
            ->join('marketing_attributions', 'marketing_attributions.id', '=', 'marketing_events.attribution_id')
            ->selectRaw('COALESCE(marketing_attributions.source, \'direct\') as source')
            ->selectRaw('COALESCE(marketing_attributions.medium, \'none\') as medium')
            ->selectRaw('COALESCE(marketing_attributions.campaign, \'(none)\') as campaign')
            ->selectRaw('COUNT(*) as conversions')
            ->selectRaw('COALESCE(SUM(marketing_events.value), 0) as revenue')
            ->groupBy('source', 'medium', 'campaign')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source,
                'medium' => $row->medium,
                'campaign' => $row->campaign,
                'conversions' => (int) $row->conversions,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();

        return [
            'range' => $range->metadata(),
            'product_views' => $productViews,
            'add_to_carts' => $addToCarts,
            'begin_checkouts' => $beginCheckouts,
            'purchases' => $purchases,
            'view_to_cart_rate' => $productViews > 0 ? round($addToCarts / $productViews * 100, 2) : 0.0,
            'cart_to_purchase_rate' => $addToCarts > 0 ? round($purchases / $addToCarts * 100, 2) : 0.0,
            'attributed_revenue' => $attributed,
        ];
    }
}
