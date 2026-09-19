<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase4CheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_selects_sslcommerz_for_bangladesh_and_stripe_otherwise(): void
    {
        config(['payments.driver' => 'gateways']);
        $cart = $this->seedCart()['token'];

        $this->postJson('/api/v1/checkout/quote', [
            'cart_token' => $cart,
            'delivery_method' => 'standard',
            'shipping_address' => ['country' => 'Bangladesh'],
        ])->assertOk()
            ->assertJsonPath('data.countryCode', 'BD')
            ->assertJsonPath('data.provider', 'sslcommerz')
            ->assertJsonPath('data.intendedProvider', 'sslcommerz');

        $this->postJson('/api/v1/checkout/quote', [
            'cart_token' => $cart,
            'delivery_method' => 'standard',
            'shipping_address' => ['country' => 'US'],
        ])->assertOk()
            ->assertJsonPath('data.countryCode', 'US')
            ->assertJsonPath('data.provider', 'stripe');
    }

    public function test_country_normalization_accepts_iso_codes_and_names(): void
    {
        config(['payments.driver' => 'gateways']);
        $cart = $this->seedCart()['token'];

        $this->postJson('/api/v1/checkout/quote', [
            'cart_token' => $cart,
            'shipping_address' => ['country' => 'BGD'],
        ])->assertOk()->assertJsonPath('data.countryCode', 'BD')->assertJsonPath('data.provider', 'sslcommerz');

        $this->postJson('/api/v1/checkout/quote', [
            'cart_token' => $cart,
            'shipping_address' => ['country' => 'Denmark'],
        ])->assertOk()->assertJsonPath('data.countryCode', 'DK')->assertJsonPath('data.provider', 'stripe');
    }

    public function test_frontend_cannot_override_provider_selection(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();
        $cart = $this->seedCart()['token'];

        $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($cart, 'Bangladesh', [
            'payment_provider' => 'stripe',
            'provider' => 'stripe',
        ]))->assertCreated()
            ->assertJsonPath('data.paymentProvider', 'sslcommerz')
            ->assertJsonPath('payment.provider', 'sslcommerz');
    }

    public function test_authenticated_customer_can_checkout_with_owned_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $address = Address::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Member',
            'email' => 'jane@example.test',
            'address' => '12 Gulshan Avenue',
            'city' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'BD',
        ]);
        $seed = $this->seedCart($user);
        Coupon::factory()->create(['code' => 'JAAJ10']);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/promo', [
            'cart_token' => $seed['token'],
            'promo_code' => 'JAAJ10',
        ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout/orders', [
                'cart_token' => $seed['token'],
                'delivery_method' => 'standard',
                'shipping_address_id' => $address->id,
                'payment' => $this->demoCard(),
            ])->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount', 20)
            ->assertJsonPath('data.total', 189.95);

        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'payment_status' => 'paid']);
    }

    public function test_address_ownership_is_enforced(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = Address::create([
            'user_id' => $owner->id,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@example.test',
            'address' => '1 Main Street',
            'city' => 'Dhaka',
            'postal_code' => '1200',
            'country' => 'Bangladesh',
        ]);
        $cart = $this->seedCart($other)['token'];

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/checkout/orders', [
                'cart_token' => $cart,
                'delivery_method' => 'standard',
                'shipping_address_id' => $address->id,
                'payment' => $this->demoCard(),
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address_id');
    }

    public function test_checkout_revalidates_stock_and_rejects_invalid_payload(): void
    {
        $seed = $this->seedCart();
        $seed['variant']->update(['stock_quantity' => 0]);
        $seed['product']->update(['stock_quantity' => 0, 'in_stock' => false]);

        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->postJson('/api/v1/checkout/orders', ['delivery_method' => 'standard'])
            ->assertUnprocessable();
    }

    public function test_stripe_initialization_and_verified_webhook_marks_paid_once(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();
        Coupon::factory()->create(['code' => 'JAAJ10', 'used_count' => 0]);
        $seed = $this->seedCart();
        $this->postJson('/api/v1/cart/promo', ['cart_token' => $seed['token'], 'promo_code' => 'JAAJ10'])->assertOk();

        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'United States'))
            ->assertCreated()
            ->assertJsonPath('data.paymentProvider', 'stripe')
            ->assertJsonPath('data.paymentStatus', 'pending')
            ->assertJsonPath('payment.redirectUrl', 'https://checkout.stripe.com/c/pay/cs_test_1');

        $this->assertSame(10, $seed['variant']->refresh()->stock_quantity);
        $this->assertSame(0, Coupon::query()->where('code', 'JAAJ10')->value('used_count'));

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();
        $payment = $order->payment;
        $payload = json_encode([
            'id' => 'evt_test_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'payment_status' => 'paid',
                    'client_reference_id' => $order->number,
                    'metadata' => ['payment_reference' => $payment->reference, 'order_number' => $order->number],
                    'amount_total' => 18995,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);
        $this->assertSame(1, Coupon::query()->where('code', 'JAAJ10')->value('used_count'));
        $this->assertSame(1, Payment::query()->where('status', 'paid')->count());
    }

    public function test_invalid_stripe_webhook_is_rejected(): void
    {
        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=nope',
            'CONTENT_TYPE' => 'application/json',
        ], '{"id":"evt"}')->assertStatus(400);
    }

    public function test_stripe_payment_failure_does_not_mark_paid_or_decrement_stock(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();
        $seed = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'Germany'))->assertCreated();
        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $payload = json_encode([
            'id' => 'evt_fail_1',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_fail',
                    'status' => 'failed',
                    'metadata' => ['payment_reference' => $order->payment->reference, 'order_number' => $order->number],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $this->assertSame('failed', $order->refresh()->payment_status);
        $this->assertNotSame('cancelled', $order->status);
        $this->assertSame(10, $seed['variant']->refresh()->stock_quantity);
        $this->assertSame('pending', $order->status);
    }

    public function test_sslcommerz_success_failure_cancel_and_duplicate_ipn(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();
        $seed = $this->seedCart();

        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'Bangladesh'))
            ->assertCreated()
            ->assertJsonPath('data.paymentProvider', 'sslcommerz')
            ->assertJsonPath('payment.redirectUrl', 'https://sandbox.sslcommerz.com/EasyCheckOut/foo');

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();
        $this->assertSame(10, $seed['variant']->refresh()->stock_quantity);

        $this->post('/api/v1/payments/sslcommerz/ipn', [
            'status' => 'VALID',
            'tran_id' => $order->payment->reference,
            'val_id' => 'val123',
            'amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'USD',
        ])->assertOk();

        $this->post('/api/v1/payments/sslcommerz/ipn', [
            'status' => 'VALID',
            'tran_id' => $order->payment->reference,
            'val_id' => 'val123',
            'amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'USD',
        ])->assertOk();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);

        $second = $this->seedCart(null, 'coat-2');
        $failed = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($second['token'], 'BD'))->assertCreated();
        $failedOrder = Order::query()->where('number', $failed->json('data.id'))->firstOrFail();

        $this->post('/api/v1/payments/sslcommerz/fail', [
            'status' => 'FAILED',
            'tran_id' => $failedOrder->payment->reference,
        ])->assertRedirect();
        $this->assertSame('failed', $failedOrder->refresh()->payment_status);
        $this->assertSame(10, $second['variant']->refresh()->stock_quantity);

        $third = $this->seedCart(null, 'coat-3');
        $canceled = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($third['token'], 'Bangladesh'))->assertCreated();
        $canceledOrder = Order::query()->where('number', $canceled->json('data.id'))->firstOrFail();
        $this->post('/api/v1/payments/sslcommerz/cancel', [
            'status' => 'CANCELLED',
            'tran_id' => $canceledOrder->payment->reference,
        ])->assertRedirect();
        $this->assertSame('canceled', $canceledOrder->refresh()->payment_status);
        $this->assertSame('pending', $canceledOrder->status);

        $this->post('/api/v1/payments/sslcommerz/ipn', [
            'status' => 'VALID',
            'tran_id' => 'missing',
            'val_id' => 'val999',
        ])->assertOk();
        $this->assertSame(1, Payment::query()->where('status', 'paid')->count());
    }

    public function test_sslcommerz_invalid_validation_is_not_marked_paid(): void
    {
        config(['payments.driver' => 'gateways']);
        Http::fake([
            'sandbox.sslcommerz.com/gwprocess/*' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/foo'], 200),
            'sandbox.sslcommerz.com/validator/*' => Http::response(['status' => 'INVALID'], 200),
        ]);
        $seed = $this->seedCart();
        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'Bangladesh'))->assertCreated();
        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $this->post('/api/v1/payments/sslcommerz/ipn', [
            'status' => 'VALID',
            'tran_id' => $order->payment->reference,
            'val_id' => 'bad',
            'amount' => '109.95',
        ])->assertOk();

        $this->assertSame('failed', $order->refresh()->payment_status);
        $this->assertSame(10, $seed['variant']->refresh()->stock_quantity);
    }

    public function test_guest_cannot_view_order_without_checkout_token(): void
    {
        $order = Order::factory()->create();
        $this->getJson('/api/v1/checkout/orders/'.$order->number)->assertForbidden();
    }

    /**
     * @return array{token:string, product:Product, variant:\App\Models\ProductVariant}
     */
    private function seedCart(?User $user = null, string $externalId = 'coat'): array
    {
        $product = Product::factory()->create([
            'external_id' => $externalId,
            'slug' => $externalId,
            'price' => 100,
            'stock_quantity' => 10,
            'in_stock' => true,
            'is_active' => true,
        ]);
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

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
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
            'payment' => $this->demoCard(),
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function gatewayPayload(string $cart, string $country, array $extra = []): array
    {
        return array_merge([
            'cart_token' => $cart,
            'delivery_method' => 'standard',
            'shipping_address' => [
                'firstName' => 'Jane',
                'lastName' => 'Member',
                'email' => 'jane@example.test',
                'address' => '1 Main Street',
                'city' => $country === 'Bangladesh' || $country === 'BD' ? 'Dhaka' : 'Berlin',
                'postalCode' => '1200',
                'country' => $country,
            ],
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    private function demoCard(): array
    {
        return [
            'card_name' => 'Jane Member',
            'card_number' => '4242 4242 4242 4242',
            'expiry' => '12/30',
            'cvc' => '123',
        ];
    }

    private function fakeGateways(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'cs_test_1',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_1',
                'status' => 'open',
            ], 200),
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
    }

    private function stripeSignature(string $payload): string
    {
        $timestamp = (string) time();
        $secret = (string) config('payments.stripe.webhook_secret');
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }
}
