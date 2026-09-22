<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase7AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    private function paidOrder(?User $user, float $total, array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'user_id' => $user?->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
            'subtotal' => $total,
            'total' => $total,
        ], $overrides));
    }

    private function addItem(Order $order, Product $product, int $quantity, ?float $lineTotal = null): void
    {
        $unit = (float) $product->price;
        $order->items()->create([
            'product_id' => $product->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'quantity' => $quantity,
            'unit_price' => $unit,
            'line_total' => $lineTotal ?? $unit * $quantity,
        ]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 50,
            'rating' => 0,
            'reviews_count' => 0,
        ], $overrides));
    }

    private function fetch(string $path, ?User $user = null)
    {
        $request = $user ? $this->actingAs($user, 'sanctum') : $this;

        return $request->getJson($path);
    }

    /* ------------------------------------------------------------------ *
     * Authorization
     * ------------------------------------------------------------------ */

    public function test_admin_can_view_overview_analytics(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview', $this->admin())
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'range',
                'revenue',
                'gross_revenue',
                'refunded_amount',
                'orders',
                'paid_orders',
                'average_order_value',
                'customers',
                'products',
                'pending_reviews',
                'wishlist_items',
            ]]);
    }

    public function test_customer_is_rejected_from_analytics(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview', $this->customer())->assertForbidden();
        $this->fetch('/api/v1/admin/analytics/sales', $this->customer())->assertForbidden();
    }

    public function test_guest_is_rejected_from_analytics(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview')->assertUnauthorized();
        $this->fetch('/api/v1/admin/analytics/products')->assertUnauthorized();
    }

    /* ------------------------------------------------------------------ *
     * Revenue
     * ------------------------------------------------------------------ */

    public function test_unpaid_orders_are_excluded_from_revenue(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $this->paidOrder($customer, 200.00);
        Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 500.00,
            'total' => 500.00,
        ]);

        $data = $this->fetch('/api/v1/admin/analytics/overview', $admin)->assertOk()->json('data');

        $this->assertEquals(200.00, $data['gross_revenue']);
        $this->assertEquals(200.00, $data['revenue']['value']);
        $this->assertEquals(1, $data['paid_orders']['value']);
        $this->assertEquals(2, $data['orders']['value']);
    }

    public function test_succeeded_refunds_reduce_net_revenue(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->paidOrder($customer, 300.00);

        Refund::create([
            'order_id' => $order->id,
            'provider' => 'demo',
            'status' => 'succeeded',
            'amount' => 120.00,
            'currency' => 'USD',
            'reason' => 'return',
            'requested_at' => now(),
        ]);

        $data = $this->fetch('/api/v1/admin/analytics/overview', $admin)->assertOk()->json('data');

        $this->assertEquals(300.00, $data['gross_revenue']);
        $this->assertEquals(120.00, $data['refunded_amount']);
        $this->assertEquals(180.00, $data['revenue']['value']);
    }

    public function test_non_succeeded_refunds_do_not_reduce_revenue(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->paidOrder($customer, 300.00);

        foreach (['pending', 'processing', 'failed', 'canceled'] as $status) {
            Refund::create([
                'order_id' => $order->id,
                'provider' => 'demo',
                'status' => $status,
                'amount' => 50.00,
                'currency' => 'USD',
                'reason' => 'return',
                'requested_at' => now(),
            ]);
        }

        $data = $this->fetch('/api/v1/admin/analytics/overview', $admin)->assertOk()->json('data');

        $this->assertEquals(0.00, $data['refunded_amount']);
        $this->assertEquals(300.00, $data['revenue']['value']);
    }

    public function test_revenue_respects_custom_date_range(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $this->paidOrder($customer, 150.00, ['created_at' => now()->subDays(15)]);
        $this->paidOrder($customer, 999.00);

        $from = now()->subDays(20)->toDateString();
        $to = now()->subDays(10)->toDateString();

        $data = $this->fetch("/api/v1/admin/analytics/overview?preset=custom&from={$from}&to={$to}", $admin)
            ->assertOk()->json('data');

        $this->assertEquals(150.00, $data['gross_revenue']);
        $this->assertEquals(1, $data['paid_orders']['value']);
    }

    /* ------------------------------------------------------------------ *
     * Average order value
     * ------------------------------------------------------------------ */

    public function test_average_order_value_uses_paid_population(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $this->paidOrder($customer, 100.00);
        $this->paidOrder($customer, 200.00);

        $data = $this->fetch('/api/v1/admin/analytics/overview', $admin)->assertOk()->json('data');

        $this->assertEquals(150.00, $data['average_order_value']['value']);
    }

    public function test_average_order_value_is_zero_without_orders(): void
    {
        $admin = $this->admin();

        $data = $this->fetch('/api/v1/admin/analytics/overview?preset=custom&from=2020-01-01&to=2020-01-02', $admin)
            ->assertOk()->json('data');

        $this->assertEquals(0.0, $data['average_order_value']['value']);
        $this->assertEquals(0.00, $data['revenue']['value']);
        $this->assertEquals(0, $data['paid_orders']['value']);
    }

    /* ------------------------------------------------------------------ *
     * Comparisons and time series
     * ------------------------------------------------------------------ */

    public function test_overview_compares_against_previous_period(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $this->paidOrder($customer, 100.00, ['created_at' => now()->subDay()]);
        $this->paidOrder($customer, 200.00);

        $data = $this->fetch('/api/v1/admin/analytics/overview?preset=today', $admin)->assertOk()->json('data');

        $this->assertEquals(200.00, $data['revenue']['value']);
        $this->assertEquals(100.00, $data['revenue']['previous']);
        $this->assertEquals(100.0, $data['revenue']['change_percent']);
        $this->assertEquals(1, $data['paid_orders']['value']);
        $this->assertEquals(1, $data['paid_orders']['previous']);
        $this->assertEquals(0.0, $data['paid_orders']['change_percent']);
    }

    public function test_sales_series_groups_by_day_with_gaps_filled(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $dayOne = now()->subDays(6)->startOfDay();
        $dayThree = now()->subDays(4)->startOfDay();

        $order = $this->paidOrder($customer, 100.00, ['created_at' => $dayOne->addHours(2)]);
        $this->paidOrder($customer, 50.00, ['created_at' => $dayThree->addHours(3)]);
        Refund::create([
            'order_id' => $order->id,
            'provider' => 'demo',
            'status' => 'succeeded',
            'amount' => 20.00,
            'currency' => 'USD',
            'reason' => 'return',
            'requested_at' => $dayThree->addHours(5),
        ]);

        $from = now()->subDays(6)->toDateString();
        $to = now()->subDays(4)->toDateString();

        $data = $this->fetch("/api/v1/admin/analytics/sales?preset=custom&from={$from}&to={$to}", $admin)
            ->assertOk()->json('data');

        $this->assertSame('day', $data['range']['group']);
        $this->assertCount(3, $data['series']);

        $periods = array_column($data['series'], 'period');
        $sorted = $periods;
        sort($sorted);
        $this->assertSame($sorted, $periods);

        $this->assertEquals(100.00, $data['series'][0]['revenue']);
        $this->assertEquals(1, $data['series'][0]['orders']);
        $this->assertEquals(0.00, $data['series'][1]['revenue']);
        $this->assertEquals(0, $data['series'][1]['orders']);
        $this->assertEquals(30.00, $data['series'][2]['revenue']);
        $this->assertEquals(20.00, $data['series'][2]['refunded_amount']);
    }

    public function test_sales_series_supports_explicit_week_and_month_grouping(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $this->paidOrder($customer, 100.00, ['created_at' => now()->subDays(10)]);
        $this->paidOrder($customer, 100.00, ['created_at' => now()->subDays(2)]);

        $from = now()->subDays(14)->toDateString();
        $to = now()->toDateString();

        $weekly = $this->fetch("/api/v1/admin/analytics/sales?preset=custom&from={$from}&to={$to}&group=week", $admin)
            ->assertOk()->json('data');

        $this->assertSame('week', $weekly['range']['group']);
        $this->assertGreaterThanOrEqual(2, count($weekly['series']));

        $total = array_sum(array_column($weekly['series'], 'orders'));
        $this->assertSame(2, (int) $total);

        $monthly = $this->fetch("/api/v1/admin/analytics/sales?preset=custom&from={$from}&to={$to}&group=month", $admin)
            ->assertOk()->json('data');

        $this->assertSame('month', $monthly['range']['group']);
        $this->assertSame(200.00, (float) array_sum(array_column($monthly['series'], 'gross_revenue')));
    }

    /* ------------------------------------------------------------------ *
     * Order breakdown
     * ------------------------------------------------------------------ */

    public function test_order_breakdown_reports_real_statuses(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status) {
            Order::factory()->create([
                'user_id' => $customer->id,
                'status' => $status,
                'payment_status' => 'paid',
                'total' => 10,
                'subtotal' => 10,
            ]);
        }

        $data = $this->fetch('/api/v1/admin/analytics/orders', $admin)->assertOk()->json('data');

        $this->assertSame(6, $data['total']);
        $this->assertCount(6, $data['by_status']);
        foreach ($data['by_status'] as $row) {
            $this->assertSame(1, $row['count']);
        }
        $this->assertContains('cancelled', array_column($data['by_status'], 'status'));
        $this->assertNotContains('paid', array_column($data['by_status'], 'status'));
    }

    public function test_order_breakdown_counts_refunded_orders(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->paidOrder($customer, 100.00);
        Refund::create([
            'order_id' => $order->id,
            'provider' => 'demo',
            'status' => 'succeeded',
            'amount' => 100.00,
            'currency' => 'USD',
            'reason' => 'order_cancellation',
            'requested_at' => now(),
        ]);

        $data = $this->fetch('/api/v1/admin/analytics/orders', $admin)->assertOk()->json('data');

        $this->assertSame(1, $data['refunded_orders']);
    }

    /* ------------------------------------------------------------------ *
     * Products and categories
     * ------------------------------------------------------------------ */

    public function test_top_products_aggregate_quantity_and_revenue(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $product = $this->product(['price' => 25]);

        $first = $this->paidOrder($customer, 50.00);
        $this->addItem($first, $product, 2);
        $second = $this->paidOrder($customer, 25.00);
        $this->addItem($second, $product, 1);

        $data = $this->fetch('/api/v1/admin/analytics/products', $admin)->assertOk()->json('data');

        $this->assertCount(1, $data['items']);
        $this->assertSame($product->external_id, $data['items'][0]['product_id']);
        $this->assertSame($product->slug, $data['items'][0]['slug']);
        $this->assertSame(3, $data['items'][0]['units_sold']);
        $this->assertEquals(75.00, $data['items'][0]['revenue']);
        $this->assertSame(2, $data['items'][0]['orders_count']);
    }

    public function test_top_products_limit_and_sort_work(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $bulk = $this->product(['price' => 10]);
        $premium = $this->product(['price' => 100]);

        $order = $this->paidOrder($customer, 150.00);
        $this->addItem($order, $bulk, 5);
        $this->addItem($order, $premium, 1);

        $byQuantity = $this->fetch('/api/v1/admin/analytics/products?sort=quantity', $admin)->assertOk()->json('data');
        $this->assertSame($bulk->external_id, $byQuantity['items'][0]['product_id']);

        $byRevenue = $this->fetch('/api/v1/admin/analytics/products?sort=revenue', $admin)->assertOk()->json('data');
        $this->assertSame($premium->external_id, $byRevenue['items'][0]['product_id']);

        $limited = $this->fetch('/api/v1/admin/analytics/products?limit=1', $admin)->assertOk()->json('data');
        $this->assertCount(1, $limited['items']);
    }

    public function test_top_products_exclude_unpaid_order_items(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $product = $this->product();

        $unpaid = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'total' => 50,
            'subtotal' => 50,
        ]);
        $this->addItem($unpaid, $product, 2);

        $data = $this->fetch('/api/v1/admin/analytics/products', $admin)->assertOk()->json('data');

        $this->assertSame([], $data['items']);
    }

    public function test_category_aggregation_is_correct(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $category = Category::factory()->create(['slug' => 'test-cat', 'label' => 'Test Cat']);
        $other = Category::factory()->create(['slug' => 'other-cat', 'label' => 'Other Cat']);
        $product = $this->product(['price' => 40, 'category_id' => $category->id]);
        $otherProduct = $this->product(['price' => 60, 'category_id' => $other->id]);

        $order = $this->paidOrder($customer, 140.00);
        $this->addItem($order, $product, 2);
        $this->addItem($order, $otherProduct, 1);

        $data = $this->fetch('/api/v1/admin/analytics/categories', $admin)->assertOk()->json('data');

        $this->assertCount(2, $data['items']);
        $this->assertSame('test-cat', $data['items'][0]['slug']);
        $this->assertEquals(80.00, $data['items'][0]['revenue']);
        $this->assertSame(2, $data['items'][0]['units_sold']);
        $this->assertSame('other-cat', $data['items'][1]['slug']);
        $this->assertEquals(60.00, $data['items'][1]['revenue']);
    }

    /* ------------------------------------------------------------------ *
     * Customers
     * ------------------------------------------------------------------ */

    public function test_customer_metrics_counts_are_correct(): void
    {
        $admin = $this->admin();
        $buyer = $this->customer(['created_at' => now()->subDays(40)]);
        $newcomer = $this->customer();
        $lurker = $this->customer();

        $this->paidOrder($buyer, 100.00);
        $this->paidOrder($buyer, 50.00);

        $data = $this->fetch('/api/v1/admin/analytics/customers', $admin)->assertOk()->json('data');

        $this->assertSame(3, $data['total_customers']);
        $this->assertSame(1, $data['purchasing_customers']);
        $this->assertSame(1, $data['repeat_customers']);
        $this->assertSame(2, $data['new_customers']);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_guest_and_authenticated_orders_are_split(): void
    {
        $admin = $this->admin();
        $this->paidOrder(null, 80.00);
        $this->paidOrder($this->customer(), 120.00);

        $data = $this->fetch('/api/v1/admin/analytics/customers', $admin)->assertOk()->json('data');

        $this->assertSame(1, $data['guest_orders']);
        $this->assertSame(1, $data['authenticated_orders']);
    }

    /* ------------------------------------------------------------------ *
     * Inventory
     * ------------------------------------------------------------------ */

    public function test_inventory_metrics_use_application_stock_rules(): void
    {
        $admin = $this->admin();
        $this->product(['stock_quantity' => 20, 'low_stock_threshold' => 5]);
        $this->product(['in_stock' => false, 'stock_quantity' => 10]);
        $this->product(['stock_quantity' => 0]);
        $this->product(['stock_quantity' => 3, 'low_stock_threshold' => 5]);
        $this->product(['stock_quantity' => 0, 'is_active' => false]);

        $data = $this->fetch('/api/v1/admin/analytics/inventory', $admin)->assertOk()->json('data');

        $this->assertSame(4, $data['active_products']);
        $this->assertSame(2, $data['out_of_stock_products']);
        $this->assertSame(1, $data['low_stock_products']);
        $this->assertSame(33, $data['total_units']);
    }

    public function test_low_stock_list_flags_attention_products(): void
    {
        $admin = $this->admin();
        $low = $this->product(['stock_quantity' => 2, 'low_stock_threshold' => 5]);
        $this->product(['stock_quantity' => 50]);

        $data = $this->fetch('/api/v1/admin/analytics/inventory', $admin)->assertOk()->json('data');

        $this->assertCount(1, $data['low_stock_list']);
        $this->assertSame($low->external_id, $data['low_stock_list'][0]['product_id']);
        $this->assertSame('low_stock', $data['low_stock_list'][0]['inventory_status']);
        $this->assertSame(5, $data['low_stock_list'][0]['low_stock_threshold']);
    }

    /* ------------------------------------------------------------------ *
     * Reviews
     * ------------------------------------------------------------------ */

    public function test_review_metrics_report_moderation_counts(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $otherProduct = $this->product();
        $user = $this->customer();

        Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);
        Review::factory()->create(['user_id' => $user->id, 'product_id' => $otherProduct->id]);
        Review::factory()->approved()->create(['user_id' => $this->customer()->id, 'product_id' => $product->id, 'rating' => 5]);
        Review::factory()->rejected()->create(['user_id' => $this->customer()->id, 'product_id' => $otherProduct->id]);

        $data = $this->fetch('/api/v1/admin/analytics/reviews', $admin)->assertOk()->json('data');

        $this->assertSame(2, $data['pending']);
        $this->assertSame(1, $data['approved']);
        $this->assertSame(1, $data['rejected']);
        $this->assertSame(4, $data['total']);
    }

    public function test_review_average_uses_approved_only(): void
    {
        $admin = $this->admin();
        $first = $this->product();
        $second = $this->product();
        $user = $this->customer();

        Review::factory()->approved()->create(['user_id' => $user->id, 'product_id' => $first->id, 'rating' => 5]);
        Review::factory()->approved()->create(['user_id' => $this->customer()->id, 'product_id' => $second->id, 'rating' => 3]);
        Review::factory()->create(['user_id' => $this->customer()->id, 'product_id' => $second->id, 'rating' => 1]);

        $data = $this->fetch('/api/v1/admin/analytics/reviews', $admin)->assertOk()->json('data');

        $this->assertEquals(4.0, $data['average_approved_rating']);
        $this->assertSame(2, $data['reviewed_products']);
    }

    /* ------------------------------------------------------------------ *
     * Wishlist
     * ------------------------------------------------------------------ */

    public function test_wishlist_metrics_counts_are_correct(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $second = $this->product();
        $inactive = $this->product(['is_active' => false]);

        $first = Wishlist::create(['user_id' => $this->customer()->id]);
        $first->items()->create(['product_id' => $product->id]);
        $first->items()->create(['product_id' => $second->id]);

        $other = Wishlist::create(['user_id' => $this->customer()->id]);
        $other->items()->create(['product_id' => $product->id]);

        $guest = Wishlist::create(['guest_token' => (string) Str::uuid()]);
        $guest->items()->create(['product_id' => $second->id]);
        $guest->items()->create(['product_id' => $inactive->id]);

        $data = $this->fetch('/api/v1/admin/analytics/wishlist', $admin)->assertOk()->json('data');

        $this->assertSame(5, $data['total_items']);
        $this->assertSame(2, $data['wishlists_with_account']);
        $this->assertSame(1, $data['guest_wishlists']);
        $this->assertSame($product->external_id, $data['top_products'][0]['product_id']);
        $this->assertSame(2, $data['top_products'][0]['saves']);
        $this->assertNotContains($inactive->external_id, array_column($data['top_products'], 'product_id'));
    }

    /* ------------------------------------------------------------------ *
     * Validation
     * ------------------------------------------------------------------ */

    public function test_invalid_preset_is_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview?preset=fortnight', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preset');
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview?preset=custom&from=not-a-date&to=2026-09-01', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from');
    }

    public function test_reversed_date_range_is_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview?preset=custom&from=2026-09-10&to=2026-09-01', $this->admin())
            ->assertUnprocessable();
    }

    public function test_oversized_date_range_is_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/overview?preset=custom&from=2024-01-01&to=2026-09-22', $this->admin())
            ->assertUnprocessable();
    }

    public function test_invalid_limit_is_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/products?limit=0', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');

        $this->fetch('/api/v1/admin/analytics/products?limit=500', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    public function test_invalid_sort_and_group_are_rejected(): void
    {
        $this->fetch('/api/v1/admin/analytics/products?sort=revenue;DROP+TABLE+orders', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');

        $this->fetch('/api/v1/admin/analytics/sales?group=hour', $this->admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('group');
    }

    /* ------------------------------------------------------------------ *
     * Security
     * ------------------------------------------------------------------ */

    public function test_analytics_responses_expose_no_sensitive_values(): void
    {
        $admin = $this->admin();
        $customer = $this->customer(['email' => 'sensitive-customer@example.com', 'phone' => '+10000000001']);
        $checkoutToken = '123e4567-e89b-12d3-a456-426614174000';
        $order = $this->paidOrder($customer, 100.00, ['checkout_token' => $checkoutToken]);
        $order->payment()->create([
            'provider' => 'demo',
            'status' => 'paid',
            'reference' => 'demo_ref_1',
            'amount' => 100,
            'amount_minor' => 10000,
            'currency' => 'USD',
            'card_last_four' => '4242',
        ]);

        $paths = [
            'overview', 'sales', 'orders', 'products', 'categories',
            'customers', 'inventory', 'reviews', 'wishlist',
        ];

        foreach ($paths as $path) {
            $body = strtolower($this->fetch("/api/v1/admin/analytics/{$path}", $admin)->assertOk()->getContent());

            foreach (['checkout_token', '123e4567-e89b-12d3', 'card_last_four', 'card_number', 'password', 'remember_token', 'secret', 'sensitive-customer', '+10000000001'] as $needle) {
                $this->assertStringNotContainsString($needle, $body, "Leak in analytics/{$path}: {$needle}");
            }
        }
    }
}
