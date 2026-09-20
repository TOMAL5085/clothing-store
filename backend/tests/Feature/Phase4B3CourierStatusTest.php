<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4B3CourierStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_courier_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status");

        $response->assertOk()
            ->assertJson(['status' => 'in_transit']);
    }

    public function test_unauthenticated_access_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'in_transit',
        ]);

        $this->getJson("/api/v1/admin/orders/{$order->number}/shipment/status")
            ->assertStatus(401);
    }

    public function test_non_admin_customer_access_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'in_transit',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status")
            ->assertForbidden();
    }

    public function test_missing_shipment_is_handled_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        // No shipment created for this order

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status");

        $response->assertNotFound()
            ->assertJson(['message' => 'No shipment found for this order.']);
    }

    public function test_endpoint_returns_courier_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'shipped',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
            'carrier_reference' => 'MOCK-123',
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status");

        $response->assertOk()
            ->assertJsonPath('status', 'shipped');
    }

    public function test_calling_endpoint_does_not_modify_shipment_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
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
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment/status");

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => $originalStatus]);
    }
}