<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Color;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase8SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(string $password = 'password123'): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => $password]);
    }

    private function seedCart(?User $user = null, int $quantity = 1): string
    {
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 50,
            'in_stock' => true,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'size_id' => Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3])->id,
            'color_id' => Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17'])->id,
            'sku' => 'SEC-M-INK-'.Str::random(6),
            'price' => 100,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $payload = ['product_id' => $product->external_id, 'size' => 'M', 'quantity' => $quantity];
        $request = $user
            ? $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', $payload)
            : $this->postJson('/api/v1/cart/items', $payload);

        $request->assertSuccessful();

        return $request->json('data.token');
    }

    /** @return array<string, mixed> */
    private function demoCheckoutPayload(string $cartToken): array
    {
        return [
            'cart_token' => $cartToken,
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
        ];
    }

    private function eligibleProductFor(User $user): Product
    {
        $product = Product::factory()->create(['price' => 60, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total' => 60,
            'subtotal' => 60,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'quantity' => 1,
            'unit_price' => 60,
            'line_total' => 60,
        ]);

        return $product;
    }

    /* ------------------------------------------------------------------ *
     * Authentication protection
     * ------------------------------------------------------------------ */

    public function test_repeated_login_attempts_are_rate_limited(): void
    {
        $this->customer();

        $last = null;
        for ($i = 0; $i < 6; $i++) {
            $last = $this->postJson('/api/v1/auth/login', [
                'email' => 'victim@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $last->assertStatus(429);
        $last->assertHeader('Retry-After');
    }

    public function test_legitimate_login_still_works(): void
    {
        $this->customer('correct-horse-123');

        $this->postJson('/api/v1/auth/login', [
            'email' => User::query()->where('role', 'customer')->firstOrFail()->email,
            'password' => 'correct-horse-123',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_does_not_reveal_account_existence(): void
    {
        $this->customer();

        $unknown = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody-here@example.test',
            'password' => 'whatever123',
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => User::query()->where('role', 'customer')->firstOrFail()->email,
            'password' => 'wrong-password',
        ]);

        $unknown->assertUnprocessable();
        $wrongPassword->assertUnprocessable();
        $this->assertSame($unknown->json('message'), $wrongPassword->json('message'));
    }

    public function test_registration_is_rate_limited(): void
    {
        $last = null;
        for ($i = 0; $i < 4; $i++) {
            $last = $this->postJson('/api/v1/auth/register', [
                'name' => 'Flooder',
                'email' => 'flood@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $last->assertStatus(429);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        $this->customer();

        $last = null;
        for ($i = 0; $i < 4; $i++) {
            $last = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'victim@example.test']);
        }

        $last->assertStatus(429);
    }

    public function test_password_reset_response_is_generic(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.test'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists, a reset link has been sent.');
    }

    /* ------------------------------------------------------------------ *
     * Guest order lookup
     * ------------------------------------------------------------------ */

    public function test_guest_lookup_with_valid_token_works(): void
    {
        $cartToken = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->demoCheckoutPayload($cartToken))->assertCreated();
        $number = $created->json('data.id');

        $order = Order::query()->where('number', $number)->firstOrFail();

        $this->getJson("/api/v1/checkout/orders/{$number}?checkout_token={$order->checkout_token}")
            ->assertOk()
            ->assertJsonPath('data.id', $number);
    }

    public function test_guest_lookup_with_invalid_token_is_rejected(): void
    {
        $cartToken = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->demoCheckoutPayload($cartToken))->assertCreated();
        $number = $created->json('data.id');

        $this->getJson("/api/v1/checkout/orders/{$number}?checkout_token=00000000-0000-0000-0000-000000000000")
            ->assertForbidden();

        $this->getJson("/api/v1/checkout/orders/{$number}")
            ->assertForbidden();
    }

    public function test_repeated_guest_lookups_are_throttled(): void
    {
        $cartToken = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->demoCheckoutPayload($cartToken))->assertCreated();
        $number = $created->json('data.id');

        $last = null;
        for ($i = 0; $i < 21; $i++) {
            $last = $this->getJson("/api/v1/checkout/orders/{$number}?checkout_token=wrong");
        }

        $last->assertStatus(429);
    }

    /* ------------------------------------------------------------------ *
     * Checkout authority and flooding
     * ------------------------------------------------------------------ */

    public function test_excessive_checkout_attempts_are_throttled(): void
    {
        $last = null;
        for ($i = 0; $i < 7; $i++) {
            $last = $this->postJson('/api/v1/checkout/orders', ['cart_token' => (string) Str::uuid()]);
        }

        $last->assertStatus(429);
    }

    public function test_client_cannot_override_payment_provider_or_totals(): void
    {
        $cartToken = $this->seedCart();

        $response = $this->postJson('/api/v1/checkout/orders', array_merge(
            $this->demoCheckoutPayload($cartToken),
            [
                'payment_provider' => 'stripe',
                'provider' => 'stripe',
                'payment_status' => 'paid',
                'status' => 'delivered',
                'total' => 1,
                'subtotal' => 1,
            ]
        ))->assertCreated();

        $order = Order::query()->where('number', $response->json('data.id'))->firstOrFail();

        $this->assertSame('demo', $order->payment_provider);
        $this->assertNotEquals(1, (float) $order->total);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotSame('delivered', $order->status);
    }

    public function test_promo_guessing_is_throttled(): void
    {
        $cartToken = $this->seedCart();

        $last = null;
        for ($i = 0; $i < 11; $i++) {
            $last = $this->postJson('/api/v1/cart/promo', [
                'cart_token' => $cartToken,
                'promo_code' => 'GUESS-'.$i,
            ]);
        }

        $last->assertStatus(429);
    }

    public function test_client_cannot_choose_discount_amount(): void
    {
        $cartToken = $this->seedCart();

        $this->postJson('/api/v1/cart/promo', [
            'cart_token' => $cartToken,
            'promo_code' => 'FAKE-99',
            'discount' => 9999,
            'discount_percent' => 100,
        ])->assertUnprocessable();
    }

    /* ------------------------------------------------------------------ *
     * Webhook idempotency and validation
     * ------------------------------------------------------------------ */

    public function test_stripe_webhook_rejects_invalid_signatures(): void
    {
        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['id' => 'evt_bad']))->assertStatus(400);
    }

    public function test_sslcommerz_duplicate_callback_is_idempotent(): void
    {
        config(['payments.driver' => 'gateways']);
        Http::fake([
            'sandbox.sslcommerz.com/gwprocess/*' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/foo',
            ], 200),
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'amount' => '200.00',
                'currency_amount' => '200.00',
                'currency' => 'USD',
                'tran_id' => 'ignored',
            ], 200),
        ]);

        $customer = $this->customer();
        $cartToken = $this->seedCart($customer, 2);
        $created = $this->postJson('/api/v1/checkout/orders', array_merge(
            $this->demoCheckoutPayload($cartToken),
            ['shipping_address' => [
                'firstName' => 'Jane', 'lastName' => 'Member', 'email' => 'jane@example.test',
                'address' => '1 Main Street', 'city' => 'Dhaka', 'postalCode' => '1200', 'country' => 'Bangladesh',
            ], 'payment' => []]
        ))->assertCreated();

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();
        $payload = ['status' => 'VALID', 'tran_id' => $order->payment->reference, 'val_id' => 'val1', 'amount' => '200.00', 'currency' => 'USD'];

        $this->post('/api/v1/payments/sslcommerz/ipn', $payload)->assertOk();
        $this->post('/api/v1/payments/sslcommerz/ipn', $payload)->assertOk();

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame(1, Payment::query()->where('order_id', $order->id)->count());
    }

    public function test_sslcommerz_callback_without_reference_changes_nothing(): void
    {
        $this->post('/api/v1/payments/sslcommerz/ipn', ['status' => 'VALID'])
            ->assertOk();

        $this->assertSame(0, Order::query()->count());
    }

    /* ------------------------------------------------------------------ *
     * Reviews, wishlist, cart, resolution abuse
     * ------------------------------------------------------------------ */

    public function test_review_spam_is_rate_limited(): void
    {
        config(['security.rate_limits.reviews' => ['per_minute' => 2, 'per_hour' => 10]]);

        $customer = $this->customer();
        $product = $this->eligibleProductFor($customer);

        $payload = ['rating' => 5, 'title' => 'Great', 'body' => 'Loved it, would buy again.'];
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $payload)->assertCreated();
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $payload)->assertUnprocessable();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $payload)->assertStatus(429);
    }

    public function test_review_client_fields_are_ignored(): void
    {
        $customer = $this->customer();
        $product = $this->eligibleProductFor($customer);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", [
                'rating' => 5,
                'body' => 'Solid quality.',
                'status' => 'approved',
                'verified_purchase' => false,
                'user_id' => 999999,
            ])->assertCreated();

        $response->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.verifiedPurchase', true);
    }

    public function test_review_requires_purchase_eligibility(): void
    {
        $customer = $this->customer();
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5, 'body' => 'Never bought this.'])
            ->assertForbidden();
    }

    public function test_storefront_mutations_are_rate_limited(): void
    {
        config(['security.rate_limits.storefront_mutations' => ['per_minute' => 2]]);

        $product = Product::factory()->create(['is_active' => true]);

        $this->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])->assertCreated();
        $this->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])->assertSuccessful();
        $this->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])->assertStatus(429);
    }

    public function test_customer_cannot_mutate_another_users_cart(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();
        $this->seedCart($owner);

        $itemId = CartItem::query()->firstOrFail()->id;

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 5])
            ->assertNotFound();
    }

    public function test_resolution_requests_are_rate_limited(): void
    {
        config(['security.rate_limits.resolution_requests' => ['per_minute' => 2, 'per_hour' => 10]]);

        $customer = $this->customer();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'total' => 100,
            'subtotal' => 100,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Changed my mind.'])
            ->assertCreated();
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Again.'])
            ->assertUnprocessable();
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Once more.'])
            ->assertStatus(429);
    }

    /* ------------------------------------------------------------------ *
     * Admin authorization, throttling, audit log
     * ------------------------------------------------------------------ */

    public function test_customer_cannot_perform_admin_mutations(): void
    {
        $customer = $this->customer();
        $order = Order::factory()->create(['status' => 'processing', 'payment_status' => 'paid', 'total' => 10, 'subtotal' => 10]);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'delivered'])
            ->assertForbidden();

        $this->assertSame('processing', $order->refresh()->status);
    }

    public function test_admin_mutations_are_rate_limited(): void
    {
        config(['security.rate_limits.admin_mutations' => ['per_minute' => 2]]);

        $admin = $this->admin();
        $order = Order::factory()->create(['status' => 'processing', 'payment_status' => 'paid', 'total' => 10, 'subtotal' => 10]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'shipped'])
            ->assertOk();
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'delivered'])
            ->assertOk();
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'cancelled'])
            ->assertStatus(429);
    }

    public function test_admin_mutation_creates_audit_event_without_secrets(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['status' => 'processing', 'payment_status' => 'paid', 'total' => 10, 'subtotal' => 10]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'shipped'])
            ->assertOk();

        $log = AuditLog::query()->where('action', 'order.status_updated')->firstOrFail();

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame($order->id, $log->auditable_id);
        $this->assertSame($order->number, $log->metadata['order_number']);
        $this->assertSame('processing', $log->metadata['previous_status']);
        $this->assertSame('shipped', $log->metadata['new_status']);
        $this->assertNotNull($log->ip);
    }

    public function test_audit_logger_strips_sensitive_metadata(): void
    {
        $admin = $this->admin();

        $log = app(AuditLogger::class)->log('order.status_updated', $admin, null, [
            'order_number' => 'JAAJ-1',
            'password' => 'hunter2',
            'card_number' => '4242424242424242',
            'checkout_token' => 'd4e5f6-token',
            'nested' => ['secret_key' => 'abc', 'ok' => 'fine'],
        ]);

        $stored = $log->fresh()->metadata;

        $this->assertSame('JAAJ-1', $stored['order_number']);
        $this->assertArrayNotHasKey('password', $stored);
        $this->assertArrayNotHasKey('card_number', $stored);
        $this->assertArrayNotHasKey('checkout_token', $stored);
        $this->assertArrayNotHasKey('secret_key', $stored['nested']);
        $this->assertSame('fine', $stored['nested']['ok']);
    }

    public function test_audit_log_read_is_admin_only_with_filters(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => 'order.status_updated']);
        AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => 'refund.updated']);

        // Unauthenticated first: actingAs persists for later requests in this test.
        $this->getJson('/api/v1/admin/audit-logs')->assertUnauthorized();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/audit-logs?action=order.status_updated')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'order.status_updated');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/audit-logs/actions')
            ->assertOk()
            ->assertJsonFragment(['order.status_updated']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/audit-logs')
            ->assertForbidden();
    }

    public function test_audit_prune_command_removes_expired_rows(): void
    {
        config(['security.audit_retention_days' => 30]);

        AuditLog::factory()->create(['created_at' => now()->subDays(60)]);
        $fresh = AuditLog::factory()->create(['created_at' => now()->subDays(5)]);

        $this->artisan('audit:prune')->assertSuccessful();

        $this->assertSame(1, AuditLog::query()->count());
        $this->assertTrue($fresh->fresh()->exists());
    }

    /* ------------------------------------------------------------------ *
     * Mass assignment and input boundaries
     * ------------------------------------------------------------------ */

    public function test_registration_cannot_set_admin_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ])->assertCreated();

        $this->assertSame('customer', User::query()->where('email', 'sneaky@example.test')->firstOrFail()->role);
    }

    public function test_profile_update_cannot_set_role(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'Renamed',
                'email' => $customer->email,
                'role' => 'admin',
            ])->assertOk();

        $this->assertSame('customer', $customer->refresh()->role);
    }

    public function test_review_update_cannot_change_owner_or_moderation(): void
    {
        $customer = $this->customer();
        $product = $this->eligibleProductFor($customer);

        $created = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 4, 'body' => 'Fine.'])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/reviews/'.$created->json('data.id'), [
                'body' => 'Edited.',
                'user_id' => $this->admin()->id,
                'status' => 'approved',
            ])->assertOk();

        $review = Review::query()->findOrFail($created->json('data.id'));
        $this->assertSame($customer->id, $review->user_id);
        $this->assertSame('pending', $review->status);
    }

    /* ------------------------------------------------------------------ *
     * Response hygiene
     * ------------------------------------------------------------------ */

    public function test_security_headers_are_present(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_api_responses_expose_no_sensitive_values(): void
    {
        $customer = $this->customer();
        $cartToken = $this->seedCart($customer);
        $created = $this->postJson('/api/v1/checkout/orders', $this->demoCheckoutPayload($cartToken))->assertCreated();
        $number = $created->json('data.id');
        $order = Order::query()->where('number', $number)->firstOrFail();

        $bodies = [
            strtolower($created->getContent()),
            strtolower($this->getJson("/api/v1/checkout/orders/{$number}?checkout_token={$order->checkout_token}")->assertOk()->getContent()),
            strtolower($this->actingAs($customer, 'sanctum')->getJson('/api/v1/auth/me')->assertOk()->getContent()),
            strtolower($this->getJson('/api/v1/products')->assertOk()->getContent()),
        ];

        foreach ($bodies as $body) {
            foreach (['card_number', '4242 4242', 'cvc', '"cvc"', 'password', 'remember_token', 'client_secret', 'webhook_secret', 'card_last_four'] as $needle) {
                $this->assertStringNotContainsString($needle, $body, "Sensitive leak: {$needle}");
            }
        }
    }
}
