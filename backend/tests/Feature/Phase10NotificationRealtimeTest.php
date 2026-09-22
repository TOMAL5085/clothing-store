<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\NotificationService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Phase10NotificationRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'status' => 'active'], $overrides));
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function orderFor(?User $user = null): Order
    {
        return Order::factory()->create([
            'user_id' => $user?->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    private function disable(User $user, string $category, array $flags): void
    {
        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            $flags
        );
    }

    /* ------------------------------------------------------------------ *
     * Preferences API
     * ------------------------------------------------------------------ */

    public function test_preferences_return_defaults_for_new_user(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notification-preferences')
            ->assertOk();

        $data = $response->json('data');
        $this->assertCount(23, $data);

        foreach ($data as $row) {
            $this->assertTrue($row['in_app_enabled']);
            $this->assertTrue($row['email_enabled']);
            $this->assertTrue($row['sms_enabled']);
            $this->assertTrue($row['whatsapp_enabled']);
            $this->assertNotEmpty($row['label']);
        }

        $categories = array_column($data, 'category');
        $this->assertContains('order_placed', $categories);
        $this->assertContains('refund_failed', $categories);
        $this->assertContains('admin_new_order', $categories);
        $this->assertContains('admin_shipment_problem', $categories);
    }

    public function test_preferences_require_authentication(): void
    {
        $this->getJson('/api/v1/notification-preferences')->assertUnauthorized();
        $this->putJson('/api/v1/notification-preferences', ['preferences' => []])->assertUnauthorized();
    }

    public function test_customer_can_update_own_preferences(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'order_placed', 'in_app_enabled' => false, 'email_enabled' => true],
                    ['category' => 'refund_failed', 'in_app_enabled' => true, 'email_enabled' => false],
                ],
            ])
            ->assertOk();

        $byCategory = collect($response->json('data'))->keyBy('category');
        $this->assertFalse($byCategory['order_placed']['in_app_enabled']);
        $this->assertTrue($byCategory['order_placed']['email_enabled']);
        $this->assertTrue($byCategory['refund_failed']['in_app_enabled']);
        $this->assertFalse($byCategory['refund_failed']['email_enabled']);
        // Untouched categories keep defaults.
        $this->assertTrue($byCategory['order_paid']['in_app_enabled']);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $customer->id,
            'category' => 'order_placed',
            'in_app_enabled' => false,
        ]);
    }

    public function test_preferences_are_scoped_to_the_caller(): void
    {
        $caller = $this->customer();
        $other = $this->customer();

        // There is no user parameter: a smuggled user_id is ignored.
        $this->actingAs($caller, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'user_id' => $other->id,
                'preferences' => [
                    ['category' => 'order_placed', 'in_app_enabled' => false],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, NotificationPreference::query()->where('user_id', $caller->id)->count());
        $this->assertSame(0, NotificationPreference::query()->where('user_id', $other->id)->count());

        $otherRows = $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/notification-preferences')
            ->assertOk()
            ->json('data');
        $this->assertTrue(collect($otherRows)->firstWhere('category', 'order_placed')['in_app_enabled']);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'order_shipped_to_mars', 'in_app_enabled' => false],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_unknown_preference_fields_are_rejected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'order_placed', 'push_enabled' => true],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_invalid_boolean_values_are_rejected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'order_placed', 'in_app_enabled' => 'yes-please'],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_duplicate_preference_rows_are_impossible(): void
    {
        $customer = $this->customer();

        NotificationPreference::factory()->create([
            'user_id' => $customer->id,
            'category' => 'order_placed',
        ]);

        $this->expectException(QueryException::class);

        NotificationPreference::factory()->create([
            'user_id' => $customer->id,
            'category' => 'order_placed',
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Preference filtering through real delivery
     * ------------------------------------------------------------------ */

    public function test_defaults_preserve_existing_notification_behavior(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OrderPlacedNotification::class,
        ]);
    }

    public function test_disabled_in_app_preference_suppresses_database_row(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);
        $this->disable($customer, 'order_placed', ['in_app_enabled' => false]);

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OrderPlacedNotification::class,
        ]);
    }

    public function test_disabled_email_preference_removes_mail_channel(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);
        $this->disable($customer, 'order_placed', ['email_enabled' => false]);

        // The mail channel is filtered out while in-app delivery stays.
        $this->assertSame(
            ['database', 'broadcast'],
            (new OrderPlacedNotification($order))->via($customer->fresh())
        );

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OrderPlacedNotification::class,
        ]);
    }

    public function test_enabled_email_preference_keeps_mail_channel(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->assertSame(
            ['database', 'mail', 'broadcast'],
            (new OrderPlacedNotification($order))->via($customer)
        );

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OrderPlacedNotification::class,
        ]);
    }

    public function test_all_channels_disabled_sends_nothing(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);
        $this->disable($customer, 'order_placed', ['in_app_enabled' => false, 'email_enabled' => false]);

        $this->assertSame([], (new OrderPlacedNotification($order))->via($customer->fresh()));

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $customer->id]);
    }

    public function test_admin_preference_filters_admin_notifications_per_recipient(): void
    {
        $optedOut = $this->admin();
        $defaultAdmin = $this->admin();
        $this->disable($optedOut, 'admin_new_order', ['in_app_enabled' => false]);

        app(NotificationService::class)->orderPlaced($this->orderFor());

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $optedOut->id,
            'type' => AdminNewOrderNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $defaultAdmin->id,
            'type' => AdminNewOrderNotification::class,
        ]);
    }

    public function test_via_includes_broadcast_by_default_and_is_filtered(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->assertSame(
            ['database', 'mail', 'broadcast'],
            (new OrderPlacedNotification($order))->via($customer)
        );

        $this->disable($customer, 'order_placed', ['in_app_enabled' => false]);

        $this->assertSame(
            ['mail'],
            (new OrderPlacedNotification($order))->via($customer->fresh())
        );
    }

    /* ------------------------------------------------------------------ *
     * Broadcast channels and payload
     * ------------------------------------------------------------------ */

    public function test_customer_broadcasts_use_private_user_channel(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $channels = (new OrderPlacedNotification($order))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-App.Models.User.'.$customer->id, $channels[0]->name);
        $this->assertSame('notification.created', (new OrderPlacedNotification($order))->broadcastAs());
    }

    public function test_admin_broadcasts_use_restricted_admin_channel(): void
    {
        $order = $this->orderFor();

        $channels = (new AdminNewOrderNotification($order))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-admin.notifications', $channels[0]->name);
    }

    public function test_broadcast_payload_is_minimal_and_safe(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $payload = (new OrderPlacedNotification($order))->broadcastWith();

        $this->assertSame(
            ['type', 'title', 'message', 'category', 'order_number', 'action_url', 'read_at'],
            array_keys($payload)
        );
        $this->assertSame('order_placed', $payload['category']);
        $this->assertSame($order->number, $payload['order_number']);

        $adminPayload = (new AdminNewOrderNotification($order))->broadcastWith();
        $this->assertArrayNotHasKey('customer_email', $adminPayload);
        $this->assertSame($customer->name, $adminPayload['customer_name']);

        $guestPayload = (new AdminNewOrderNotification($this->orderFor()))->broadcastWith();
        $this->assertSame('Guest', $guestPayload['customer_name']);

        // The action_url carries the same owner checkout_token deep link as
        // the persisted record and order emails (existing convention); it
        // only ever travels the recipient's own private channel.
        $this->assertStringContainsString('checkout_token', $payload['action_url']);

        $blob = strtolower(json_encode([$payload, $adminPayload]));
        foreach (['password', 'webhook', 'secret', 'card_number', 'cvv', 'cvc', 'customer_email'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob, "Leak in broadcast payload: {$needle}");
        }
    }

    /* ------------------------------------------------------------------ *
     * Broadcast channel authorization
     * ------------------------------------------------------------------ */

    private function authPayload(string $channel): array
    {
        return ['socket_id' => '1234.5678', 'channel_name' => 'private-'.$channel];
    }

    /**
     * Channel authorization is driver-independent, but channel callbacks
     * live on the broadcaster instance for the boot-time default driver.
     * These tests point at the pusher-protocol driver with dummy
     * credentials (signature checks run locally, no network involved) and
     * then re-register the channel file onto that instance, mirroring what
     * provider boot does in a real deployment with a fixed driver.
     */
    private function usePusherDriver(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-pusher-key',
            'broadcasting.connections.pusher.secret' => 'test-pusher-secret',
            'broadcasting.connections.pusher.app_id' => 'test-pusher-app',
        ]);

        require base_path('routes/channels.php');
    }

    public function test_broadcast_auth_allows_own_channel(): void
    {
        $this->usePusherDriver();
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/broadcasting/auth', $this->authPayload('App.Models.User.'.$customer->id))
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_broadcast_auth_rejects_other_users_channel(): void
    {
        $this->usePusherDriver();
        $customer = $this->customer();
        $other = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/broadcasting/auth', $this->authPayload('App.Models.User.'.$other->id))
            ->assertForbidden();
    }

    public function test_broadcast_auth_rejects_guests(): void
    {
        $this->usePusherDriver();
        $customer = $this->customer();

        $this->postJson('/broadcasting/auth', $this->authPayload('App.Models.User.'.$customer->id))
            ->assertUnauthorized();
    }

    public function test_broadcast_auth_protects_admin_channel(): void
    {
        $this->usePusherDriver();
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/broadcasting/auth', $this->authPayload('admin.notifications'))
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/broadcasting/auth', $this->authPayload('admin.notifications'))
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ *
     * Real-time flow integrity
     * ------------------------------------------------------------------ */

    public function test_broadcast_transport_creates_no_extra_database_rows(): void
    {
        // phpunit uses the null broadcast driver: no socket server needed and
        // broadcasting must never add database records of its own.
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->assertContains('broadcast', (new OrderPlacedNotification($order))->via($customer));

        $customer->notify(new OrderPlacedNotification($order));

        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_database_channel_runs_before_broadcast(): void
    {
        // Ordering contract: persistence first (source of truth), realtime
        // second (supplementary transport). The sender delivers channels in
        // via() order.
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $channels = (new OrderPlacedNotification($order))->via($customer);

        $this->assertLessThan(
            array_search('broadcast', $channels, true),
            array_search('database', $channels, true)
        );
    }

    public function test_notifications_stay_queued_after_commit(): void
    {
        $notification = new OrderPlacedNotification($this->orderFor());

        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }
}
