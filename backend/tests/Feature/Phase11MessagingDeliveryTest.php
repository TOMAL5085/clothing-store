<?php

namespace Tests\Feature;

use App\Jobs\SendOutboundMessage;
use App\Models\CancellationRequest;
use App\Models\Color;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\CancellationRequestedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Messaging\MessageGatewayException;
use App\Services\Messaging\MessageGatewayResolver;
use App\Services\Messaging\MockSmsGateway;
use App\Services\Messaging\MockWhatsappGateway;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase11MessagingDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MockSmsGateway::reset();
        MockWhatsappGateway::reset();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function customer(?string $phone = '+15550001111'): User
    {
        return User::factory()->create(['role' => 'customer', 'phone' => $phone]);
    }

    private function orderFor(?User $user = null): Order
    {
        return Order::factory()->create([
            'user_id' => $user?->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    private function disablePref(User $user, string $category, array $flags): void
    {
        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            $flags
        );
    }

    /* ------------------------------------------------------------------ *
     * Configuration
     * ------------------------------------------------------------------ */

    public function test_mock_sms_driver_sends_deterministically(): void
    {
        $gateway = app(MessageGatewayResolver::class)->for('sms');

        $this->assertInstanceOf(MockSmsGateway::class, $gateway);

        $result = $gateway->send('+15550001111', 'Hello JAAJ', ['idempotency_key' => 'key-1']);

        $this->assertSame('sent', $result['status']);
        $this->assertNotEmpty($result['provider_message_id']);
        $this->assertCount(1, MockSmsGateway::sent());
        $this->assertSame('key-1', MockSmsGateway::sent()[0]['idempotency_key']);
    }

    public function test_mock_whatsapp_driver_sends_deterministically(): void
    {
        $gateway = app(MessageGatewayResolver::class)->for('whatsapp');

        $this->assertInstanceOf(MockWhatsappGateway::class, $gateway);

        $result = $gateway->send('+15550001111', 'Hello JAAJ');

        $this->assertSame('sent', $result['status']);
        $this->assertCount(1, MockWhatsappGateway::sent());
        $this->assertCount(0, MockSmsGateway::sent());
    }

    public function test_unknown_driver_fails_safely_without_side_effects(): void
    {
        config(['messaging.sms_driver' => 'bogus-driver']);

        $customer = $this->customer();
        $order = $this->orderFor($customer);

        try {
            app(MessageGatewayResolver::class)->for('sms');
            $this->fail('Unknown driver should throw.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('bogus-driver', $exception->getMessage());
        }

        // And the dispatcher path never throws into business flows.
        app(MessageDispatcher::class)->dispatchFor($customer, new OrderPlacedNotification($order));

        $this->assertSame(0, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame([], MockSmsGateway::sent());
    }

    public function test_dispatcher_reports_unconfigured_driver_without_rows(): void
    {
        config(['messaging.sms_driver' => 'bogus-driver']);

        $customer = $this->customer();

        app(MessageDispatcher::class)->dispatchFor(
            $customer,
            new OrderPlacedNotification($this->orderFor($customer))
        );

        // WhatsApp (mock) still delivered; SMS skipped without a row.
        $this->assertSame(0, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'whatsapp')->count());
    }

    /* ------------------------------------------------------------------ *
     * Preferences
     * ------------------------------------------------------------------ */

    public function test_sms_disabled_means_no_sms_delivery(): void
    {
        $customer = $this->customer();
        $this->disablePref($customer, 'order_placed', ['sms_enabled' => false]);

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertSame(0, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'whatsapp')->count());
        $this->assertSame([], MockSmsGateway::sent());
    }

    public function test_sms_enabled_permits_delivery_by_default(): void
    {
        $customer = $this->customer();

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $sms = NotificationDelivery::query()->where('channel', 'sms')->firstOrFail();
        $this->assertSame('sent', $sms->status);
        $this->assertSame('mock-sms', $sms->provider);
        $this->assertNotEmpty($sms->provider_message_id);
        $this->assertSame(1, count(MockSmsGateway::sent()));
    }

    public function test_whatsapp_disabled_means_no_whatsapp_delivery(): void
    {
        $customer = $this->customer();
        $this->disablePref($customer, 'order_placed', ['whatsapp_enabled' => false]);

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertSame(0, NotificationDelivery::query()->where('channel', 'whatsapp')->count());
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame([], MockWhatsappGateway::sent());
    }

    /* ------------------------------------------------------------------ *
     * Eligibility
     * ------------------------------------------------------------------ */

    public function test_missing_phone_skips_delivery_safely(): void
    {
        $customer = $this->customer(null);

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertSame(0, NotificationDelivery::query()->count());
        $this->assertSame([], MockSmsGateway::sent());
        $this->assertSame([], MockWhatsappGateway::sent());
    }

    public function test_invalid_phone_skips_delivery_safely(): void
    {
        $customer = $this->customer('not-a-phone-number');

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertSame(0, NotificationDelivery::query()->count());
    }

    public function test_unsupported_category_generates_no_delivery(): void
    {
        $customer = $this->customer();

        $notification = new CancellationRequestedNotification(
            $this->orderFor($customer),
            new CancellationRequest(['reason' => 'Changed my mind.'])
        );

        app(MessageDispatcher::class)->dispatchFor($customer, $notification);

        $this->assertSame(0, NotificationDelivery::query()->count());
        $this->assertSame([], MockSmsGateway::sent());
    }

    public function test_admin_categories_are_excluded_from_messaging(): void
    {
        $admin = $this->admin(['phone' => '+15550002222']);

        app(NotificationService::class)->orderPlaced($this->orderFor());

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => AdminNewOrderNotification::class,
        ]);
        $this->assertSame(0, NotificationDelivery::query()->where('user_id', $admin->id)->count());
    }

    public function test_phone_normalization_rules(): void
    {
        $this->assertSame('+15550001111', MessageDispatcher::normalizePhone('+1 (555) 000-1111'));
        $this->assertSame('15550001111', MessageDispatcher::normalizePhone('15550001111'));
        $this->assertNull(MessageDispatcher::normalizePhone(null));
        $this->assertNull(MessageDispatcher::normalizePhone(''));
        $this->assertNull(MessageDispatcher::normalizePhone('not-a-phone'));
        $this->assertNull(MessageDispatcher::normalizePhone('123'));
        $this->assertNull(MessageDispatcher::normalizePhone('+15550001111111111'));
    }

    /* ------------------------------------------------------------------ *
     * Queue behavior
     * ------------------------------------------------------------------ */

    public function test_messaging_jobs_are_queued_per_channel(): void
    {
        Queue::fake();

        $customer = $this->customer();

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        Queue::assertPushed(SendOutboundMessage::class, 2);

        // Rows exist in queued state even though workers have not run.
        $this->assertSame(2, NotificationDelivery::query()->where('status', 'queued')->count());
    }

    public function test_database_notification_persists_in_the_same_flow(): void
    {
        $customer = $this->customer();

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OrderPlacedNotification::class,
        ]);
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'whatsapp')->count());
    }

    public function test_rollback_discards_delivery_rows(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        try {
            DB::transaction(function () use ($customer, $order) {
                app(MessageDispatcher::class)->dispatchFor(
                    $customer,
                    new OrderPlacedNotification($order)
                );

                throw new \RuntimeException('Simulated business failure.');
            });
            $this->fail('Transaction should have rolled back.');
        } catch (\RuntimeException) {
            // Expected.
        }

        $this->assertSame(0, NotificationDelivery::query()->count());
    }

    public function test_checkout_does_not_depend_on_messaging(): void
    {
        // The mock rejects this recipient, simulating a provider outage.
        $customer = $this->customer('+15550000000');

        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 50,
            'in_stock' => true,
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'size_id' => Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3])->id,
            'color_id' => Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17'])->id,
            'sku' => 'MSG-M-INK-'.Str::random(6),
            'price' => 100,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $cartToken = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->external_id,
                'size' => 'M',
                'quantity' => 1,
            ])
            ->assertSuccessful()
            ->json('data.token');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/checkout/orders', [
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
            ])->assertCreated();

        $order = Order::query()->where('number', $response->json('data.id'))->firstOrFail();
        $this->assertSame('paid', $order->payment_status);

        // Messaging failed cleanly in the background: rows exist, marked failed.
        $this->assertSame(2, NotificationDelivery::query()->where('user_id', $customer->id)->count());
        $this->assertSame(
            0,
            NotificationDelivery::query()->where('user_id', $customer->id)->where('status', 'sent')->count()
        );
    }

    /* ------------------------------------------------------------------ *
     * Reliability
     * ------------------------------------------------------------------ */

    public function test_successful_send_is_recorded(): void
    {
        $customer = $this->customer();

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $sms = NotificationDelivery::query()->where('channel', 'sms')->firstOrFail();

        $this->assertSame('sent', $sms->status);
        $this->assertSame('mock-sms', $sms->provider);
        $this->assertNotEmpty($sms->provider_message_id);
        $this->assertSame(1, $sms->attempts);
        $this->assertNotNull($sms->last_attempted_at);
        $this->assertNull($sms->failed_at);
        $this->assertNull($sms->delivered_at);
        $this->assertNull($sms->error_code);
        $this->assertSame('order_placed', $sms->template);
        $this->assertNotEmpty($sms->order_number);
    }

    public function test_failure_is_recorded_with_error_code(): void
    {
        $customer = $this->customer('+15550000000');

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $sms = NotificationDelivery::query()->where('channel', 'sms')->firstOrFail();

        $this->assertSame('failed', $sms->status);
        $this->assertSame('mock_rejected', $sms->error_code);
        $this->assertNotNull($sms->failed_at);
        $this->assertNull($sms->provider_message_id);
        $this->assertSame([], MockSmsGateway::sent());
    }

    public function test_retry_attempts_then_succeeds(): void
    {
        $customer = $this->customer('+15550000000');
        $resolver = app(MessageGatewayResolver::class);

        $delivery = NotificationDelivery::factory()->create([
            'user_id' => $customer->id,
            'channel' => 'sms',
            'recipient' => '+15550000000',
            'status' => 'queued',
        ]);

        try {
            (new SendOutboundMessage($delivery->id, 'Hello JAAJ'))->handle($resolver);
            $this->fail('Failing recipient should throw.');
        } catch (MessageGatewayException $exception) {
            $this->assertSame('mock_rejected', $exception->errorCode());
        }

        $this->assertSame(1, $delivery->fresh()->attempts);
        $this->assertSame('queued', $delivery->fresh()->status);

        $delivery->update(['recipient' => '+15550001111']);
        (new SendOutboundMessage($delivery->id, 'Hello JAAJ'))->handle($resolver);

        $fresh = $delivery->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(2, $fresh->attempts);
        $this->assertCount(1, MockSmsGateway::sent());
    }

    public function test_repeated_processing_does_not_duplicate_messages(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);
        $notification = new OrderPlacedNotification($order);

        $dispatcher = app(MessageDispatcher::class);
        $dispatcher->dispatchFor($customer, $notification);
        $dispatcher->dispatchFor($customer, $notification);

        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'sms')->count());
        $this->assertSame(1, NotificationDelivery::query()->where('channel', 'whatsapp')->count());
        $this->assertCount(1, MockSmsGateway::sent());

        // Re-running a completed job never resends.
        $delivery = NotificationDelivery::query()->where('channel', 'sms')->firstOrFail();
        (new SendOutboundMessage($delivery->id, 'Hello JAAJ'))->handle(app(MessageGatewayResolver::class));

        $this->assertCount(1, MockSmsGateway::sent());
    }

    public function test_job_tries_and_backoff_are_bounded(): void
    {
        $job = new SendOutboundMessage(1, 'Hello JAAJ');

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 300], $job->backoff());
    }

    /* ------------------------------------------------------------------ *
     * Security
     * ------------------------------------------------------------------ */

    public function test_no_arbitrary_message_sending_endpoints_exist(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/sms', ['to' => '+15550001111', 'message' => 'Hi'])
            ->assertNotFound();
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/messages', ['to' => '+15550001111', 'message' => 'Hi'])
            ->assertNotFound();
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/notifications/send', ['message' => 'Hi'])
            ->assertNotFound();
    }

    public function test_deliveries_never_leak_across_users(): void
    {
        $customer = $this->customer();
        $other = $this->customer('+15550002222');

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertSame(0, NotificationDelivery::query()->where('user_id', $other->id)->count());
    }

    public function test_logs_contain_no_secrets(): void
    {
        $captured = [];
        $spy = Log::spy();
        $spy->shouldReceive('warning')->andReturnUsing(function ($message, $context = []) use (&$captured) {
            $captured[] = json_encode([$message, $context]);
        });
        $spy->shouldReceive('info')->andReturnUsing(function ($message, $context = []) use (&$captured) {
            $captured[] = json_encode([$message, $context]);
        });

        $customer = $this->customer('+15550000000');

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $this->assertNotEmpty($captured);
        $blob = strtolower(implode("\n", $captured));

        foreach (['password', 'secret', 'card_number', 'cardnumber', 'cvv', 'cvc', 'authorization', 'bearer', 'checkout_token', 'remember_token'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob, "Secret leak in logs: {$needle}");
        }
    }

    public function test_delivery_records_and_payloads_hold_no_sensitive_data(): void
    {
        $customer = $this->customer();

        app(NotificationService::class)->orderPlaced($this->orderFor($customer));

        $sms = NotificationDelivery::query()->where('channel', 'sms')->firstOrFail();

        $this->assertSame(
            ['id', 'message_key', 'notification_id', 'user_id', 'channel', 'recipient', 'template', 'order_number', 'status', 'provider', 'provider_message_id', 'attempts', 'last_attempted_at', 'delivered_at', 'failed_at', 'error_code', 'created_at', 'updated_at'],
            array_keys($sms->toArray())
        );

        $blob = strtolower(json_encode($sms->toArray()).json_encode(MockSmsGateway::sent()));

        foreach (['password', 'secret', 'card_number', 'cvv', 'cvc', 'checkout_token', 'remember_token', 'authorization'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob, "Sensitive data leak: {$needle}");
        }

        $this->assertLessThanOrEqual(160, mb_strlen(MockSmsGateway::sent()[0]['message']));
    }
}
