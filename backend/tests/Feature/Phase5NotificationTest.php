<?php

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Color;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\Size;
use App\Models\User;
use App\Notifications\AdminCancellationRequestNotification;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\AdminRefundActionRequiredNotification;
use App\Notifications\AdminReturnRequestNotification;
use App\Notifications\AdminShipmentProblemNotification;
use App\Notifications\CancellationApprovedNotification;
use App\Notifications\CancellationCompletedNotification;
use App\Notifications\CancellationRejectedNotification;
use App\Notifications\CancellationRequestedNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderPaymentFailedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\RefundCompletedNotification;
use App\Notifications\RefundCreatedNotification;
use App\Notifications\RefundFailedNotification;
use App\Notifications\RefundProcessingNotification;
use App\Notifications\ReturnApprovedNotification;
use App\Notifications\ReturnReceivedNotification;
use App\Notifications\ReturnRejectedNotification;
use App\Notifications\ReturnRequestedNotification;
use App\Notifications\ShipmentStatusChangedNotification;
use App\Services\Couriers\CourierGateway;
use App\Services\ShippingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase5NotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Order, 1: ProductVariant, 2: Product} */
    private function paidOrderWithItem(string $status = 'processing', int $quantity = 2, ?User $user = null, string $provider = 'demo'): array
    {
        $customer = $user ?? User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 10 - $quantity,
            'in_stock' => true,
            'is_active' => true,
        ]);
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
            'payment_provider' => $provider,
            'currency' => 'USD',
            'subtotal' => 100 * $quantity,
            'shipping' => 0,
            'total' => 100 * $quantity,
            'inventory_decremented_at' => now(),
        ]);
        $order->payment()->create([
            'provider' => $provider,
            'status' => 'paid',
            'reference' => $provider.'_'.strtolower(fake()->bothify('????####')),
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  string  $status  Initial shipment status.
     */
    private function shipmentFor(Order $order, string $status): Shipment
    {
        return $order->shipment()->create([
            'status' => $status,
            'carrier' => 'Test Carrier',
            'tracking_number' => 'TRK123',
        ]);
    }

    /**
     * Submit a customer return request through the public API.
     *
     * @return string The created return request id.
     */
    private function submitReturn(Order $order, int $quantity = 1): string
    {
        $item = $order->items()->firstOrFail();

        $response = $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'The item arrived damaged.',
                'items' => [['order_item_id' => $item->id, 'quantity' => $quantity]],
            ])
            ->assertOk();

        return (string) $response->json('data.id');
    }

    /**
     * @return string The refund id created when the return is received.
     */
    private function approveAndReceiveReturn(string $returnId, User $admin): string
    {
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'approved'])
            ->assertOk();

        $received = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/returns/{$returnId}/received", ['admin_reason' => 'Package received.'])
            ->assertOk();

        return (string) $received->json('data.refund.id');
    }

    private function rejectReturn(string $returnId, User $admin): void
    {
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'rejected'])
            ->assertOk();
    }

    /**
     * Post a verified Stripe webhook event and assert it was accepted.
     *
     * @param  array<string, mixed>  $event
     */
    private function postStripeWebhook(array $event): void
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/v1/payments/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload),
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function stripePaymentEvent(string $id, string $type, Order $order, array $object = []): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'data' => [
                'object' => array_merge([
                    'payment_status' => 'paid',
                    'status' => 'succeeded',
                    'metadata' => [
                        'payment_reference' => $order->payment->reference,
                        'order_number' => $order->number,
                    ],
                ], $object),
            ],
        ];
    }

    private function stripeSignature(string $payload): string
    {
        $timestamp = (string) time();
        $secret = (string) config('payments.stripe.webhook_secret');
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }

    /** @return array<string, mixed> */
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

    /** @return array<string, string> */
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
        ]);
    }

    /**
     * Build a renderable text body for a mail message so content assertions do not
     * depend on the markdown view.
     */
    private function mailText(MailMessage $mail): string
    {
        return strtolower(implode(' ', array_merge(
            [(string) $mail->subject, (string) $mail->greeting],
            $mail->introLines,
            [(string) ($mail->actionText ?? ''), (string) ($mail->actionUrl ?? '')],
        )));
    }

    /* ------------------------------------------------------------------ *
     * End-to-end checkout
     * ------------------------------------------------------------------ */

    /**
     * Test: A successful demo checkout dispatches order-placed and payment-confirmed
     * notifications for both the customer and the admins.
     */
    public function test_successful_demo_checkout_sends_customer_and_admin_notifications(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertCreated();

        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'payment_status' => 'paid']);
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);

        Notification::assertSentTo($customer, OrderPlacedNotification::class);
        Notification::assertSentTo($customer, OrderPaidNotification::class);
        Notification::assertSentTo($admin, AdminNewOrderNotification::class);
        Notification::assertSentTo($admin, AdminOrderPaidNotification::class);
    }

    /**
     * Test: A guest checkout never produces customer notifications but still alerts admins.
     */
    public function test_guest_demo_checkout_sends_admin_notifications_only(): void
    {
        $admin = $this->admin();
        $seed = $this->seedCart(null);

        Notification::fake();

        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertCreated();

        Notification::assertSentTimes(OrderPlacedNotification::class, 0);
        Notification::assertSentTimes(OrderPaidNotification::class, 0);
        Notification::assertSentTimes(AdminNewOrderNotification::class, 1);
        Notification::assertSentTimes(AdminOrderPaidNotification::class, 1);
        Notification::assertSentTo($admin, AdminNewOrderNotification::class);
        Notification::assertSentTo($admin, AdminOrderPaidNotification::class);
    }

    /**
     * Test: A checkout that fails its in-transaction inventory check rolls back and
     * sends nothing, because notifications are deferred with DB::afterCommit().
     */
    public function test_failed_checkout_sends_no_notifications(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);
        $seed['variant']->update(['stock_quantity' => 0]);
        $seed['product']->update(['stock_quantity' => 0, 'in_stock' => false]);

        Notification::fake();

        $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        Notification::assertNothingSent();
        $this->assertSame(0, Order::query()->count());
    }

    /* ------------------------------------------------------------------ *
     * Payment webhooks
     * ------------------------------------------------------------------ */

    /**
     * Test: A verified Stripe webhook confirms payment and notifies customer and admins.
     */
    public function test_stripe_webhook_confirms_payment_and_sends_paid_notifications(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();

        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'United States'))
            ->assertCreated()
            ->assertJsonPath('data.paymentProvider', 'stripe');

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $this->postStripeWebhook($this->stripePaymentEvent(
            'evt_paid_1',
            'checkout.session.completed',
            $order,
            ['client_reference_id' => $order->number, 'id' => 'cs_test_1']
        ));

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);

        Notification::assertSentTo($customer, OrderPaidNotification::class);
        Notification::assertSentTo($admin, AdminOrderPaidNotification::class);
    }

    /**
     * Test: Replaying payment confirmation (a second, distinct provider event for the same
     * payment) does not duplicate notifications, orders, or stock decrements.
     */
    public function test_duplicate_payment_webhook_sends_paid_notifications_once(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();

        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'United States'))
            ->assertCreated();

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $this->postStripeWebhook($this->stripePaymentEvent(
            'evt_paid_1',
            'checkout.session.completed',
            $order,
            ['client_reference_id' => $order->number, 'id' => 'cs_test_1']
        ));
        $this->postStripeWebhook($this->stripePaymentEvent(
            'evt_paid_2',
            'payment_intent.succeeded',
            $order,
            ['id' => 'pi_123']
        ));

        Notification::assertSentTimes(OrderPaidNotification::class, 1);
        Notification::assertSentTimes(AdminOrderPaidNotification::class, 1);
        Notification::assertSentTimes(OrderPlacedNotification::class, 1);

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Payment::query()->where('status', 'paid')->count());
        $this->assertSame(8, $seed['variant']->refresh()->stock_quantity);
    }

    /**
     * Test: A payment failure webhook notifies the customer without ever sending
     * payment-confirmed notifications.
     */
    public function test_payment_failure_webhook_sends_payment_failed_notification(): void
    {
        config(['payments.driver' => 'gateways']);
        $this->fakeGateways();

        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $created = $this->postJson('/api/v1/checkout/orders', $this->gatewayPayload($seed['token'], 'Germany'))
            ->assertCreated();

        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $this->postStripeWebhook([
            'id' => 'evt_fail_1',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_fail',
                    'status' => 'failed',
                    'metadata' => [
                        'payment_reference' => $order->payment->reference,
                        'order_number' => $order->number,
                    ],
                ],
            ],
        ]);

        $this->assertSame('failed', $order->refresh()->payment_status);
        $this->assertSame(10, $seed['variant']->refresh()->stock_quantity);

        Notification::assertSentTo($customer, OrderPaymentFailedNotification::class);
        Notification::assertSentTimes(OrderPaidNotification::class, 0);
        Notification::assertSentTimes(AdminOrderPaidNotification::class, 0);
        Notification::assertSentTimes(AdminNewOrderNotification::class, 1);
    }

    /* ------------------------------------------------------------------ *
     * Shipment lifecycle
     * ------------------------------------------------------------------ */

    /**
     * Test: A normal shipment transition notifies the customer and not the admins.
     */
    public function test_shipment_status_transition_notifies_customer_not_admins(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem('processing');
        $shipment = $this->shipmentFor($order, 'ready_to_ship');

        Notification::fake();

        app(ShippingService::class)->updateShipment($shipment->fresh(), ['status' => 'shipped']);

        $this->assertSame('shipped', $shipment->fresh()->status);
        $this->assertSame(1, $shipment->events()->where('status', 'shipped')->count());

        Notification::assertSentTo($order->user, ShipmentStatusChangedNotification::class);
        Notification::assertNotSentTo($admin, AdminShipmentProblemNotification::class);
        Notification::assertSentTimes(AdminShipmentProblemNotification::class, 0);
    }

    /**
     * Test: A failed delivery notifies the customer and raises the admin alert.
     */
    public function test_failed_delivery_notifies_customer_and_admins(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem('processing');
        $shipment = $this->shipmentFor($order, 'out_for_delivery');

        Notification::fake();

        app(ShippingService::class)->updateShipment($shipment->fresh(), ['status' => 'failed_delivery']);

        $this->assertSame('failed_delivery', $shipment->fresh()->status);

        Notification::assertSentTo($order->user, ShipmentStatusChangedNotification::class);
        Notification::assertSentTo($admin, AdminShipmentProblemNotification::class);
    }

    /**
     * Test: Two courier synchronizations that report the same external status produce a
     * single notification, one status change and one tracking event.
     */
    public function test_courier_sync_is_idempotent_for_notifications(): void
    {
        $gateway = new class implements CourierGateway
        {
            public function name(): string
            {
                return 'stub';
            }

            public function createShipment(Order $order, Shipment $shipment): array
            {
                return [
                    'tracking_number' => 'STUB-1',
                    'carrier_reference' => null,
                    'status' => 'shipped',
                    'estimated_delivery_at' => null,
                    'carrier' => 'Stub Courier',
                ];
            }

            public function getTracking(Shipment $shipment): array
            {
                return [
                    'status' => 'in_transit',
                    'tracking_number' => $shipment->tracking_number,
                    'estimated_delivery_at' => null,
                    'events' => [],
                ];
            }

            public function getStatus(Shipment $shipment): string
            {
                return 'in_transit';
            }

            public function mapExternalStatusToInternal(string $rawStatus): string
            {
                return $rawStatus;
            }

            public function cancelShipment(Shipment $shipment): bool
            {
                return true;
            }
        };

        app()->instance(CourierGateway::class, $gateway);

        [$order] = $this->paidOrderWithItem('processing');
        $shipment = $this->shipmentFor($order, 'shipped');

        Notification::fake();

        $shipping = app(ShippingService::class);
        $shipping->syncShipmentStatus($shipment->fresh());
        $shipping->syncShipmentStatus($shipment->fresh());

        $this->assertSame('in_transit', $shipment->fresh()->status);
        $this->assertSame(1, $shipment->events()->where('status', 'in_transit')->count());
        Notification::assertSentTimes(ShipmentStatusChangedNotification::class, 1);
    }

    /**
     * Test: Submitting the same shipment status twice does not duplicate the notification.
     */
    public function test_identical_shipment_status_update_does_not_duplicate_notification(): void
    {
        [$order] = $this->paidOrderWithItem('processing');
        $shipment = $this->shipmentFor($order, 'ready_to_ship');

        Notification::fake();

        $shipping = app(ShippingService::class);
        $shipping->updateShipment($shipment->fresh(), ['status' => 'shipped']);
        $shipping->updateShipment($shipment->fresh(), ['status' => 'shipped']);

        $this->assertSame(1, $shipment->events()->count());
        Notification::assertSentTimes(ShipmentStatusChangedNotification::class, 1);
    }

    /* ------------------------------------------------------------------ *
     * Cancellation
     * ------------------------------------------------------------------ */

    /**
     * Test: A cancellation request submitted by the customer notifies both parties.
     */
    public function test_cancellation_request_notifies_customer_and_admins(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'pending');

        Notification::fake();

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'I ordered the wrong size.'])
            ->assertCreated();

        Notification::assertSentTo($order->user, CancellationRequestedNotification::class);
        Notification::assertSentTo($admin, AdminCancellationRequestNotification::class);
    }

    /**
     * Test: Approving a cancellation notifies the customer of approval and completion.
     */
    public function test_cancellation_approval_notifies_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'pending');

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Ordered by mistake.'])
            ->assertCreated();

        $cancellationId = CancellationRequest::query()->firstOrFail()->id;

        Notification::fake();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'approved'])
            ->assertOk();

        Notification::assertSentTo($order->user, CancellationApprovedNotification::class);
        Notification::assertSentTo($order->user, CancellationCompletedNotification::class);
        Notification::assertSentTimes(CancellationRejectedNotification::class, 0);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => 'pending',
            'reason' => 'order_cancellation',
        ]);
    }

    /**
     * Test: Rejecting a cancellation notifies the customer.
     */
    public function test_cancellation_rejection_notifies_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'pending');

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Ordered by mistake.'])
            ->assertCreated();

        $cancellationId = CancellationRequest::query()->firstOrFail()->id;

        Notification::fake();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'rejected'])
            ->assertOk();

        Notification::assertSentTo($order->user, CancellationRejectedNotification::class);
        Notification::assertSentTimes(CancellationApprovedNotification::class, 0);
        Notification::assertSentTimes(CancellationCompletedNotification::class, 0);
    }

    /* ------------------------------------------------------------------ *
     * Returns and refunds
     * ------------------------------------------------------------------ */

    /**
     * Test: A return request notifies the customer and the admins.
     */
    public function test_return_request_notifies_customer_and_admins(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);

        Notification::fake();

        $this->submitReturn($order);

        Notification::assertSentTo($order->user, ReturnRequestedNotification::class);
        Notification::assertSentTo($admin, AdminReturnRequestNotification::class);
    }

    /**
     * Test: Approving a return notifies the customer.
     */
    public function test_return_approval_notifies_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);
        $returnId = $this->submitReturn($order);

        Notification::fake();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'approved'])
            ->assertOk();

        Notification::assertSentTo($order->user, ReturnApprovedNotification::class);
        Notification::assertSentTimes(ReturnRejectedNotification::class, 0);
    }

    /**
     * Test: Rejecting a return notifies the customer.
     */
    public function test_return_rejection_notifies_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);
        $returnId = $this->submitReturn($order);

        Notification::fake();

        $this->rejectReturn($returnId, $admin);

        Notification::assertSentTo($order->user, ReturnRejectedNotification::class);
        Notification::assertSentTimes(ReturnApprovedNotification::class, 0);
    }

    /**
     * Test: Receiving an approved return notifies the customer, creates the refund and — for
     * the demo provider — never raises the admin action-required alert.
     */
    public function test_return_received_creates_refund_without_admin_action_for_demo_provider(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);

        Notification::fake();

        $returnId = $this->submitReturn($order);
        $refundId = $this->approveAndReceiveReturn($returnId, $admin);

        Notification::assertSentTo($order->user, ReturnReceivedNotification::class);
        Notification::assertSentTo($order->user, RefundCreatedNotification::class);
        Notification::assertSentTimes(AdminRefundActionRequiredNotification::class, 0);
        $this->assertNotEmpty($refundId);
    }

    /**
     * Test: Refund status transitions reported through the admin API notify the customer.
     */
    public function test_refund_status_transitions_notify_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);
        $returnId = $this->submitReturn($order);

        Notification::fake();

        $refundId = $this->approveAndReceiveReturn($returnId, $admin);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", ['status' => 'processing'])
            ->assertOk();

        Notification::assertSentTo($order->user, RefundProcessingNotification::class);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", ['status' => 'succeeded', 'provider_reference' => 'rf_ok_1'])
            ->assertOk();

        Notification::assertSentTo($order->user, RefundCompletedNotification::class);
        Notification::assertSentTimes(RefundFailedNotification::class, 0);
    }

    /**
     * Test: A refund rejected by the provider notifies the customer of the failure.
     */
    public function test_failed_refund_notifies_customer(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'pending');

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Ordered by mistake.'])
            ->assertCreated();

        $cancellationId = CancellationRequest::query()->firstOrFail()->id;
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'approved'])
            ->assertOk();

        $refundId = Refund::query()->latest('id')->firstOrFail()->id;

        Notification::fake();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", [
                'status' => 'failed',
                'failure_reason' => 'Provider declined the refund.',
            ])
            ->assertOk();

        Notification::assertSentTo($order->user, RefundFailedNotification::class);
        Notification::assertSentTimes(RefundProcessingNotification::class, 0);
        Notification::assertSentTimes(RefundCompletedNotification::class, 0);
    }

    /**
     * Test: A pending refund that is not backed by the demo provider asks an admin to act.
     */
    public function test_refund_pending_on_real_provider_notifies_admin(): void
    {
        $admin = $this->admin();
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3, provider: 'stripe');

        Notification::fake();

        $returnId = $this->submitReturn($order);
        $this->approveAndReceiveReturn($returnId, $admin);

        Notification::assertSentTo($order->user, RefundCreatedNotification::class);
        Notification::assertSentTo($admin, AdminRefundActionRequiredNotification::class);
    }

    /* ------------------------------------------------------------------ *
     * Customer notification API
     * ------------------------------------------------------------------ */

    /**
     * Test: Customer notification API - list notifications
     */
    public function test_customer_can_list_notifications(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'pending', 'payment_status' => 'pending']);

        $customer->notify(new OrderPlacedNotification($order));

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'message', 'category', 'order_number'],
                ],
            ]);
    }

    /**
     * Test: Customer notification list honours pagination metadata.
     */
    public function test_customer_notifications_are_paginated(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $customer->notify(new OrderPlacedNotification($order));
        $customer->notify(new OrderPaidNotification($order));

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * Test: Customer unread count
     */
    public function test_customer_unread_count(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $customer->notify(new OrderPlacedNotification($order));
        $customer->notify(new OrderPaidNotification($order));
        $customer->notifications()->latest()->first()->markAsRead();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    /**
     * Test: Customer mark one as read
     */
    public function test_customer_mark_one_as_read(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $customer->notify(new OrderPlacedNotification($order));
        $notification = $customer->notifications()->latest()->first();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('message', 'Notification marked as read');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test: Customer mark all as read
     */
    public function test_customer_mark_all_as_read(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $customer->notify(new OrderPlacedNotification($order));
        $customer->notify(new OrderPaidNotification($order));

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read');

        $this->assertSame(0, $customer->unreadNotifications()->count());
    }

    /* ------------------------------------------------------------------ *
     * Admin notification API
     * ------------------------------------------------------------------ */

    /**
     * Test: Admin notification API - list notifications, scoped to admin categories only.
     */
    public function test_admin_can_list_notifications(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $admin->notify(new AdminNewOrderNotification($order));
        $admin->notify(new AdminOrderPaidNotification($order));
        // A customer-category row must never surface on the admin endpoint.
        $admin->notify(new OrderPlacedNotification($order));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'message', 'category', 'order_number', 'customer_name'],
                ],
            ]);

        foreach ($response->json('data') as $row) {
            $this->assertStringStartsWith('admin_', (string) $row['category']);
        }
    }

    /**
     * Test: Admin unread count only counts admin-category notifications.
     */
    public function test_admin_unread_count(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $admin->notify(new AdminNewOrderNotification($order));
        $admin->notify(new AdminOrderPaidNotification($order));
        $admin->notify(new OrderPlacedNotification($order));

        $admin->notifications()
            ->where('type', AdminNewOrderNotification::class)
            ->first()
            ->markAsRead();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    /**
     * Test: Admin mark one as read
     */
    public function test_admin_mark_one_as_read(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);
        $admin->notify(new AdminNewOrderNotification($order));
        $notification = $admin->notifications()->latest()->first();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('message', 'Notification marked as read');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Test: Admin mark all as read only touches admin-category notifications.
     */
    public function test_admin_mark_all_as_read(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $admin->notify(new AdminNewOrderNotification($order));
        $admin->notify(new AdminOrderPaidNotification($order));
        $admin->notify(new OrderPlacedNotification($order));

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('message', 'All admin notifications marked as read');

        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame(
            0,
            $admin->unreadNotifications()
                ->whereRaw("(data::json->>'category') like ?", ['admin\_%'])
                ->count()
        );
    }

    /* ------------------------------------------------------------------ *
     * Authorization
     * ------------------------------------------------------------------ */

    /**
     * Test: Authorization - unauthenticated notification requests are rejected
     */
    public function test_unauthenticated_notification_requests_rejected(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/admin/notifications')->assertUnauthorized();
    }

    /**
     * Test: Authorization - customer cannot access admin notifications
     */
    public function test_customer_cannot_access_admin_notifications(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/notifications')
            ->assertForbidden();
    }

    /**
     * Test: Authorization - customer cannot access another customer's notifications
     */
    public function test_customer_cannot_access_other_customer_notifications(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()->create(['user_id' => $customerB->id]);
        $customerB->notify(new OrderPlacedNotification($order));
        $notification = $customerB->notifications()->latest()->first();

        $this->actingAs($customerA, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Persistence, email and payload hygiene
     * ------------------------------------------------------------------ */

    /**
     * Test: A dispatched notification is persisted as a database row with the expected shape.
     */
    public function test_notification_is_persisted_as_database_row(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $customer->notify(new OrderPlacedNotification($order));

        $row = DB::table('notifications')
            ->where('notifiable_id', $customer->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertTrue(Str::isUuid($row->id));
        $this->assertNull($row->read_at);
        $this->assertSame(User::class, $row->notifiable_type);
        $this->assertSame(OrderPlacedNotification::class, $row->type);

        $data = json_decode($row->data, true);
        $this->assertSame(
            ['type', 'title', 'message', 'category', 'order_id', 'order_number', 'action_url'],
            array_keys($data)
        );
        $this->assertSame('order_placed', $data['type']);
        $this->assertSame($order->id, $data['order_id']);
        $this->assertSame($order->number, $data['order_number']);
    }

    /**
     * Test: Email versions of the notifications carry the order data and are queued.
     */
    public function test_email_notifications_are_built_with_order_data(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $created = $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertCreated();
        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $sent = Notification::sent($customer, OrderPlacedNotification::class);
        $this->assertCount(1, $sent);

        $customerNotification = $sent->first();
        $this->assertInstanceOf(ShouldQueue::class, $customerNotification);
        $this->assertContains('mail', $customerNotification->via($customer));

        $customerMail = $customerNotification->toMail($customer);
        $this->assertSame('Order Placed Successfully', $customerMail->subject);
        $customerText = $this->mailText($customerMail);
        $this->assertStringContainsString(strtolower('Order Number: '.$order->number), $customerText);
        $this->assertStringContainsString(strtolower($customer->name), $customerText);

        $adminSent = Notification::sent($admin, AdminNewOrderNotification::class);
        $this->assertCount(1, $adminSent);
        $this->assertContains('mail', $adminSent->first()->via($admin));

        $adminMail = $adminSent->first()->toMail($admin);
        $this->assertStringStartsWith('[ADMIN]', (string) $adminMail->subject);
        $this->assertStringContainsString(
            strtolower('Order Number: '.$order->number),
            $this->mailText($adminMail)
        );
    }

    /**
     * Test: Notification payloads never carry payment credentials or account secrets.
     */
    public function test_no_sensitive_data_in_notification_payloads(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);
        $seed = $this->seedCart($customer);

        Notification::fake();

        $created = $this->postJson('/api/v1/checkout/orders', $this->demoPayload($seed['token']))
            ->assertCreated();
        $order = Order::query()->where('number', $created->json('data.id'))->firstOrFail();

        $customerNotification = Notification::sent($customer, OrderPlacedNotification::class)->first();
        $adminNotification = Notification::sent($admin, AdminNewOrderNotification::class)->first();

        $customerPayload = json_encode($customerNotification->toArray($customer));
        $adminPayload = json_encode($adminNotification->toArray($admin));

        foreach ([$customerPayload, $adminPayload] as $payload) {
            $json = strtolower((string) $payload);
            $this->assertStringNotContainsString('card', $json);
            $this->assertStringNotContainsString('4242 4242 4242 4242', $json);
            $this->assertStringNotContainsString('cvv', $json);
            $this->assertStringNotContainsString('cvc', $json);
            $this->assertStringNotContainsString('secret', $json);
            $this->assertStringNotContainsString('password', $json);
        }

        // The checkout_token deep link is an owner-facing URL, not a stored secret.
        $customerData = json_decode((string) $customerPayload, true);
        $this->assertStringContainsString('checkout_token', (string) $customerData['action_url']);
        $this->assertSame($order->number, $customerData['order_number']);
        $this->assertSame($order->number, json_decode((string) $adminPayload, true)['order_number']);
    }
}
