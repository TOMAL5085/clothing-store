<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\CmsContent;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\MarketingEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase15FinalQaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'status' => 'active'], $overrides));
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 100,
            'stock_quantity' => 10,
            'in_stock' => true,
            'is_active' => true,
        ], $overrides));
    }

    /** @return array{token: string, product: Product, variant: ProductVariant} */
    private function seedCart(?User $user = null, string $externalId = 'coat'): array
    {
        $product = $this->product(['external_id' => $externalId, 'slug' => $externalId]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $variant = $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => strtoupper($externalId).'-M-INK',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $request = $user
            ? $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => $externalId, 'size' => 'M', 'quantity' => 2])
            : $this->postJson('/api/v1/cart/items', ['product_id' => $externalId, 'size' => 'M', 'quantity' => 2]);

        return ['token' => $request->json('data.token'), 'product' => $product, 'variant' => $variant];
    }

    /** @return array<string, mixed> */
    private function demoPayload(string $cart, array $extra = []): array
    {
        return array_merge([
            'cart_token' => $cart,
            'delivery_method' => 'standard',
            'shipping_address' => [
                'firstName' => 'Jane',
                'lastName' => 'Member',
                'email' => 'jane@example.test',
                'address' => '1 Main Street',
                'city' => 'Copenhagen',
                'postalCode' => '1000',
                'country' => 'Denmark',
            ],
            'payment' => [
                'card_name' => 'Jane Member',
                'card_number' => '4242 4242 4242 4242',
                'expiry' => '12/30',
                'cvc' => '123',
            ],
        ], $extra);
    }

    /** @return array{0: Order, 1: ProductVariant, 2: Product} */
    private function paidOrderWithItem(string $status = 'processing', int $quantity = 2): array
    {
        $customer = $this->customer();
        $product = $this->product(['stock_quantity' => 10 - $quantity]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $variant = $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'RES-M-INK',
            'price' => 100,
            'stock_quantity' => 10 - $quantity,
            'is_active' => true,
        ]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
            'currency' => 'USD',
            'subtotal' => 100 * $quantity,
            'shipping' => 0,
            'total' => 100 * $quantity,
            'inventory_decremented_at' => now(),
        ]);
        $order->payment()->create([
            'provider' => 'demo',
            'status' => 'paid',
            'reference' => 'demo_'.strtolower(fake()->bothify('????####')),
            'amount' => $order->total,
            'amount_minor' => (int) ((float) $order->total * 100),
            'currency' => 'USD',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => 100,
            'line_total' => 100 * $quantity,
        ]);

        return [$order->fresh(['user', 'items']), $variant, $product];
    }

    /* ------------------------------------------------------------------ *
     * Optional-auth regression (real Bearer headers, not actingAs)
     * ------------------------------------------------------------------ */

    public function test_authenticated_checkout_creates_customer_owned_order(): void
    {
        // Regression: public routes must resolve Sanctum Bearer tokens.
        // $request->user() uses the session guard on unguarded routes and
        // silently degraded authenticated storefront traffic to guest.
        $customer = $this->customer();
        $token = $customer->createToken('qa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $product = $this->product(['external_id' => 'qa-coat', 'slug' => 'qa-coat']);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'QA-COAT-M-INK',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartToken = $this->withHeaders($headers)
            ->postJson('/api/v1/cart/items', ['product_id' => 'qa-coat', 'size' => 'M', 'quantity' => 2])
            ->assertCreated()
            ->json('data.token');

        $created = $this->withHeaders($headers)
            ->postJson('/api/v1/checkout/orders', $this->demoPayload($cartToken))
            ->assertCreated();
        $order = Order::where('number', $created->json('data.id'))->firstOrFail();

        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(2, $customer->notifications()->count());

        $history = $this->withHeaders($headers)->getJson('/api/v1/orders')->assertOk()->json('data');
        $this->assertCount(1, $history);
    }

    public function test_authenticated_wishlist_resolves_to_user_wishlist(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('qa')->plainTextToken;
        $product = $this->product();

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated();

        $this->assertDatabaseHas('wishlists', ['user_id' => $customer->id]);
    }

    public function test_authenticated_cart_resolves_to_user_cart(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('qa')->plainTextToken;
        $product = $this->product(['external_id' => 'qa-cart', 'slug' => 'qa-cart']);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'QA-CART-M-INK',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/cart/items', ['product_id' => 'qa-cart', 'size' => 'M', 'quantity' => 1])
            ->assertCreated();

        $this->assertDatabaseHas('carts', ['user_id' => $customer->id, 'guest_token' => $response->json('data.token')]);
    }

    public function test_marketing_event_attributes_authenticated_user(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('qa')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/marketing/events', [
                'event_name' => 'page_view',
                'anonymous_id' => (string) Str::uuid(),
                'consent' => ['analytics' => true, 'marketing' => false],
                'metadata' => ['path' => '/shop'],
            ])
            ->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame($customer->id, $event->user_id);
    }

    /* ------------------------------------------------------------------ *
     * Public storefront
     * ------------------------------------------------------------------ */

    public function test_public_product_listing_returns_paginated_structure(): void
    {
        $this->product();
        $this->product();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonCount(2, 'data');
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->getJson("/api/v1/products/{$product->slug}")->assertNotFound();
    }

    public function test_categories_list_ok(): void
    {
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_public_cms_shows_only_visible_content(): void
    {
        CmsContent::factory()->create(['key' => 'live-block', 'status' => 'published']);
        CmsContent::factory()->create(['key' => 'draft-block', 'status' => 'draft']);

        $keys = array_column($this->getJson('/api/v1/cms/content')->assertOk()->json('data'), 'key');

        $this->assertContains('live-block', $keys);
        $this->assertNotContains('draft-block', $keys);
    }

    public function test_public_banners_show_only_visible_slides(): void
    {
        Banner::factory()->create(['key' => 'live-slide', 'status' => 'published', 'sort_order' => 0]);
        Banner::factory()->create(['key' => 'draft-slide', 'status' => 'draft', 'sort_order' => 0]);

        $keys = array_column($this->getJson('/api/v1/banners')->assertOk()->json('data'), 'key');

        $this->assertSame(['live-slide'], $keys);
    }

    public function test_marketing_ingestion_rejects_unknown_events(): void
    {
        $this->postJson('/api/v1/marketing/events', ['event_name' => 'nope'])->assertUnprocessable();
    }

    /* ------------------------------------------------------------------ *
     * Auth boundaries
     * ------------------------------------------------------------------ */

    public function test_guest_cannot_access_authenticated_apis(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/orders')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_apis(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/admin/orders')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/admin/analytics/overview')->assertForbidden();
    }

    public function test_customer_cannot_read_another_customers_order(): void
    {
        [$order] = $this->paidOrderWithItem();
        $other = $this->customer();

        $this->actingAs($other, 'sanctum')->getJson("/api/v1/orders/{$order->number}")->assertForbidden();
    }

    public function test_registration_login_logout_lifecycle(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'QA Member',
            'email' => 'qa@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'qa@example.test',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $user = User::where('email', 'qa@example.test')->firstOrFail();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me')->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout')->assertOk();
    }

    /* ------------------------------------------------------------------ *
     * Shopping
     * ------------------------------------------------------------------ */

    public function test_cart_add_update_remove_and_totals(): void
    {
        $seed = $this->seedCart();

        $itemId = $this->postJson('/api/v1/cart/items', ['cart_token' => $seed['token'], 'product_id' => 'coat', 'size' => 'M', 'quantity' => 1])
            ->assertOk()
            ->json('data.token');

        $this->assertNotEmpty($itemId);
        $this->assertSame($seed['token'], $itemId);

        $cart = $this->getJson('/api/v1/cart?cart_token='.$seed['token'])->assertOk()->json('data');
        $this->assertSame(300.0, (float) $cart['summary']['subtotal']);

        $itemKey = $cart['items'][0]['id'];

        $updated = $this->patchJson("/api/v1/cart/items/{$itemKey}", ['cart_token' => $seed['token'], 'quantity' => 1])
            ->assertOk()
            ->json('data');
        $this->assertSame(100.0, (float) $updated['summary']['subtotal']);

        $this->deleteJson("/api/v1/cart/items/{$itemKey}?cart_token={$seed['token']}")->assertOk();

        $emptied = $this->getJson('/api/v1/cart?cart_token='.$seed['token'])->assertOk()->json('data');
        $this->assertSame([], $emptied['items']);
        $this->assertSame(0.0, (float) $emptied['summary']['subtotal']);
    }

    public function test_cart_rejects_unknown_product(): void
    {
        $this->postJson('/api/v1/cart/items', ['product_id' => 'no-such-product', 'size' => 'M', 'quantity' => 1])
            ->assertUnprocessable();
    }

    public function test_promo_valid_applies_discount_and_invalid_rejected(): void
    {
        Coupon::factory()->create(['code' => 'JAAJ10']);
        $seed = $this->seedCart();

        $withPromo = $this->postJson('/api/v1/cart/promo', [
            'cart_token' => $seed['token'],
            'promo_code' => 'JAAJ10',
        ])->assertOk()->json('data');

        $this->assertGreaterThan(0, (float) $withPromo['summary']['discount']);

        $this->postJson('/api/v1/cart/promo', [
            'cart_token' => $seed['token'],
            'promo_code' => 'BOGUS',
        ])->assertUnprocessable();
    }

    /* ------------------------------------------------------------------ *
     * Checkout and payments
     * ------------------------------------------------------------------ */

    public function test_demo_checkout_creates_paid_order_and_decrements_stock(): void
    {
        $customer = $this->customer();
        $seed = $this->seedCart($customer);

        Notification::fake();

        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertCreated()
            ->assertJsonPath('data.paymentStatus', 'paid');

        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'payment_status' => 'paid']);
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);

        Notification::assertSentTo($customer, OrderPlacedNotification::class);
    }

    public function test_payment_provider_routing_is_server_authoritative(): void
    {
        $seed = $this->seedCart();

        // Denmark is not Bangladesh: provider must resolve to stripe even
        // when the client claims otherwise (gateways mode not needed here;
        // demo driver still ignores the spoofed fields entirely).
        $response = $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token'], [
            'payment_provider' => 'stripe',
            'provider' => 'sslcommerz',
        ]))->assertCreated();

        $this->assertSame('demo', $response->json('data.paymentProvider'));
    }

    public function test_order_confirmation_requires_token_for_guests(): void
    {
        // Guest cart (no actingAs) so the checkout below is truly guest-owned.
        $seed = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))->assertCreated();
        $number = $created->json('data.id');

        $this->getJson("/api/v1/checkout/orders/{$number}")->assertForbidden();

        $order = Order::where('number', $number)->firstOrFail();
        $this->getJson("/api/v1/checkout/orders/{$number}?checkout_token={$order->checkout_token}")->assertOk();
    }

    public function test_order_history_lists_own_orders_only(): void
    {
        $customer = $this->customer();
        $seed = $this->seedCart($customer);
        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))->assertCreated();

        $mine = $this->actingAs($customer, 'sanctum')->getJson('/api/v1/orders')->assertOk()->json('data');
        $this->assertCount(1, $mine);

        $other = $this->customer();
        $this->actingAs($other, 'sanctum')->getJson('/api/v1/orders')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_can_progress_order_status(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem('processing');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'shipped'])
            ->assertOk();

        $this->assertSame('shipped', $order->fresh()->status);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'bogus'])
            ->assertUnprocessable();
    }

    /* ------------------------------------------------------------------ *
     * Cancellation / returns / refunds
     * ------------------------------------------------------------------ */

    public function test_cancellation_request_and_admin_approval(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem('processing');

        $created = $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Changed my mind.'])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$created->json('data.id')}", ['decision' => 'approved'])
            ->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_return_approve_receive_creates_refund(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem('delivered', 3);
        $item = $order->items()->firstOrFail();

        $created = $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Damaged.',
                'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
            ])
            ->assertOk();

        $returnId = $created->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'approved'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/returns/{$returnId}/received", ['admin_reason' => 'Package received.'])
            ->assertOk();

        $this->assertDatabaseHas('refunds', ['order_id' => $order->id]);
    }

    /* ------------------------------------------------------------------ *
     * Notifications and preferences
     * ------------------------------------------------------------------ */

    public function test_notifications_list_unread_and_mark_read(): void
    {
        $customer = $this->customer();
        [$order] = $this->paidOrderWithItem();
        $order->update(['user_id' => $customer->id]);

        $customer->notify(new OrderPlacedNotification($order->fresh()));

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $notificationId = $customer->notifications()->firstOrFail()->id;

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/notifications/{$notificationId}/read")
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_notification_preference_suppresses_channel(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'order_placed', 'in_app_enabled' => false, 'email_enabled' => true],
                ],
            ])
            ->assertOk();

        $byCategory = collect($response->json('data'))->keyBy('category');
        $this->assertFalse($byCategory['order_placed']['in_app_enabled']);
        $this->assertTrue($byCategory['order_placed']['email_enabled']);
    }

    /* ------------------------------------------------------------------ *
     * Reviews and wishlist
     * ------------------------------------------------------------------ */

    public function test_review_endpoints_require_authentication(): void
    {
        $product = $this->product();

        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5, 'body' => 'Great.'])
            ->assertUnauthorized();
    }

    public function test_wishlist_add_remove_flow(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated()
            ->assertJsonPath('data.ids.0', $product->external_id);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/wishlist/items/{$product->external_id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.ids');
    }

    /* ------------------------------------------------------------------ *
     * Marketing authority
     * ------------------------------------------------------------------ */

    public function test_browser_cannot_manufacture_purchase_event(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/events', [
                'event_name' => 'purchase',
                'order_number' => 'JAAJ-1',
                'value' => 9999,
            ])
            ->assertUnprocessable();

        $this->assertSame(0, MarketingEvent::query()->where('event_name', 'purchase')->count());
    }

    public function test_paid_order_emits_single_server_purchase_event(): void
    {
        $customer = $this->customer();
        $seed = $this->seedCart($customer);

        $created = $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))->assertCreated();
        $order = Order::where('number', $created->json('data.id'))->firstOrFail();

        $events = MarketingEvent::query()
            ->where('event_name', 'purchase')
            ->where('order_id', $order->id)
            ->get();

        $this->assertCount(1, $events);
        $this->assertEquals((float) $order->total, (float) $events->first()->value);
    }

    /* ------------------------------------------------------------------ *
     * Audit, health, safety
     * ------------------------------------------------------------------ */

    public function test_admin_mutation_writes_audit_log(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->slug}/inventory", [
                'variant_id' => null,
                'mode' => 'set',
                'quantity' => 42,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'inventory.adjusted']);
    }

    public function test_health_endpoint_ok_with_request_id(): void
    {
        $response = $this->get('/up')->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_representative_responses_contain_no_secrets(): void
    {
        $product = $this->product();

        $bodies = [
            strtolower($this->getJson('/api/v1/products')->assertOk()->getContent()),
            strtolower($this->getJson("/api/v1/products/{$product->slug}")->assertOk()->getContent()),
            strtolower($this->getJson('/api/v1/cms/content')->assertOk()->getContent()),
            strtolower($this->getJson('/api/v1/banners')->assertOk()->getContent()),
        ];

        foreach ($bodies as $body) {
            foreach (['password', 'secret', 'checkout_token', 'remember_token', 'card_number', 'webhook'] as $needle) {
                $this->assertStringNotContainsString($needle, $body, "Leak: {$needle}");
            }
        }
    }

    public function test_admin_analytics_requires_admin(): void
    {
        $this->actingAs($this->customer(), 'sanctum')
            ->getJson('/api/v1/admin/analytics/overview')
            ->assertForbidden();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/analytics/overview')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_pagination_meta_present_on_collections(): void
    {
        $this->product();

        $this->getJson('/api/v1/products?per_page=1')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.per_page', 1);
    }
}
