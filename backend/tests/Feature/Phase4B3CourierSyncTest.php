<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4B3CourierSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_synchronize_courier_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        // Initially pending, sync should update to in_transit via mock courier
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $response->assertOk();
        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'in_transit']);
        $this->assertDatabaseHas('shipment_events', ['shipment_id' => $shipment->id, 'status' => 'in_transit']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $order->shipment()->create(['status' => 'pending']);

        $this->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync")
            ->assertStatus(401);
    }

    public function test_non_admin_returns_403(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $order->shipment()->create(['status' => 'pending']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync")
            ->assertForbidden();
    }

    public function test_missing_shipment_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $response->assertNotFound();
    }

    public function test_changed_courier_status_updates_shipment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'in_transit']);
    }

    public function test_changed_status_creates_exactly_one_shipment_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $this->assertDatabaseCount('shipment_events', 1);
    }

    public function test_repeated_synchronization_with_same_status_creates_no_additional_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        // First sync - status is already in_transit, should not create event
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $this->assertDatabaseCount('shipment_events', 0);

        // Second sync - still in_transit, should still have 0 events
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $this->assertDatabaseCount('shipment_events', 0);
    }

    public function test_unknown_status_does_not_modify_shipment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $originalStatus = $shipment->status;

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment/status/sync");

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => $originalStatus]);
        $this->assertDatabaseCount('shipment_events', 0);
    }

    public function test_existing_get_shipment_status_remains_read_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'number' => 'TEST-001',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        // GET endpoint should not modify shipment
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status");

        $response->assertOk()
            ->assertJsonPath('status', 'pending');

        // Shipment status should remain pending
        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'pending']);
        $this->assertDatabaseCount('shipment_events', 0);
    }
}