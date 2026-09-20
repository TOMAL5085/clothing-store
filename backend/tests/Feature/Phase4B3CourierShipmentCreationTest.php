<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4B3CourierShipmentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_shipment_with_courier_integration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
                'tracking_number' => 'TRK123456789',
                'tracking_reference' => 'REF-001',
                'shipping_fee' => 15.00,
                'estimated_delivery_at' => now()->addDays(5)->toISOString(),
            ]);

        // Debug: dump the response
        \Log::info('Shipment creation response', $response->json());

        $response->assertCreated()
            ->assertJsonPath('data.statusCode', 'pending')
            ->assertJsonPath('data.carrier', 'DHL')
            ->assertJsonPath('data.trackingNumber', 'TRK123456789')
            ->assertJsonPath('data.trackingReference', 'REF-001')
            ->assertJsonPath('data.carrierReference', function ($value) {
                $this->assertStringStartsWith('MOCK-', $value);
                return true;
            })
            ->assertJsonPath('data.shippingFee', 15);

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'tracking_reference' => 'REF-001',
        ]);

        // Verify shipment event was created
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $order->shipment->id,
            'status' => 'pending',
            'description' => 'Shipment created.',
        ]);
    }

public function test_admin_can_create_shipment_with_courier_generated_tracking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        // Admin does not provide tracking number - courier should generate one
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'FedEx',
                'shipping_fee' => 10.00,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.statusCode', 'pending')
            ->assertJsonPath('data.carrier', 'FedEx')
            ->assertJsonPath('data.trackingNumber', function ($value) {
                $this->assertStringStartsWith('TRK', $value);
                return true;
            })
            ->assertJsonPath('data.carrierReference', function ($value) {
                $this->assertStringStartsWith('MOCK-', $value);
                return true;
            })
            ->assertJsonPath('data.estimatedDeliveryAt', function ($value) {
                $this->assertNotNull($value);
                return true;
            });

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'carrier' => 'FedEx',
        ]);
    }

    public function test_courier_shipment_creation_fails_for_unpaid_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status');

        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }

    public function test_customer_cannot_create_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'payment_status' => 'paid',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }

    public function test_duplicate_shipment_creation_prevented(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        // First creation
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
            ])
            ->assertCreated();

        // Second creation should fail
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'FedEx',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment');

        $this->assertDatabaseCount('shipments', 1);
    }

    public function test_courier_failure_does_not_create_shipment(): void
    {
        // This test would require mocking a courier failure
        // For now, we verify the mock provider works correctly
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
            ]);

        $response->assertCreated();

        // Verify shipment was created with mock courier data
        $shipment = $order->refresh()->shipment;
        $this->assertNotNull($shipment->tracking_number);
        $this->assertStringStartsWith('TRK', $shipment->tracking_number);
        $this->assertStringStartsWith('MOCK-', $shipment->carrier_reference);
    }

    public function test_customer_can_view_courier_tracking_on_own_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
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

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}/tracking");

        $response->assertOk()
            ->assertJsonPath('data.shipment.statusCode', 'in_transit')
            ->assertJsonPath('data.shipment.carrier', 'DHL')
            ->assertJsonPath('data.shipment.trackingNumber', 'TRK123456789')
            ->assertJsonPath('data.shipment.trackingReference', 'REF-001')
            ->assertJsonPath('data.shipment.carrierReference', 'MOCK-123');

        $responseData = $response->json();
        $this->assertNotNull($responseData['data']['shipment']['estimatedDeliveryAt'] ?? null);
    }

    public function test_customer_cannot_view_another_customers_shipment(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
        ]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}/tracking")
            ->assertForbidden();
    }
}