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

class Phase4BShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_shipment_for_paid_order(): void
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

        $response->assertCreated()
            ->assertJsonPath('data.statusCode', 'pending')
            ->assertJsonPath('data.carrier', 'DHL')
            ->assertJsonPath('data.trackingNumber', 'TRK123456789')
            ->assertJsonPath('data.trackingReference', 'REF-001');

        $this->assertSame(15, $response->json('data.shippingFee'));

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
        ]);
    }

    public function test_admin_cannot_create_shipment_for_unpaid_order(): void
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
    }

    public function test_admin_can_view_shipment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'shipped',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}/shipment");

        $response->assertOk()
            ->assertJsonPath('data.trackingNumber', 'TRK123456789')
            ->assertJsonPath('data.statusCode', 'shipped');
    }

    public function test_admin_can_update_shipment_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'processing',
            'carrier' => 'DHL',
        ]);

        // Valid transition: processing -> ready_to_ship
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.statusCode', 'ready_to_ship');

        $shipment->refresh();
        $this->assertSame('ready_to_ship', $shipment->status);
    }

    public function test_shipment_status_transitions_are_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'shipped',
            'carrier' => 'DHL',
        ]);

        // Valid transition: shipped -> in_transit
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'in_transit',
            ])
            ->assertOk()
            ->assertJsonPath('data.statusCode', 'in_transit');

        // Invalid transition: in_transit -> pending (backwards)
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'pending',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_customer_can_view_own_order_with_shipment(): void
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
            'estimated_delivery_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}");

        $response->assertOk()
            ->assertJsonPath('data.shipment.statusCode', 'in_transit')
            ->assertJsonPath('data.shipment.carrier', 'DHL')
            ->assertJsonPath('data.shipment.trackingNumber', 'TRK123456789');
    }

    public function test_customer_cannot_view_another_customers_order(): void
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
            ->getJson("/api/v1/orders/{$order->number}")
            ->assertForbidden();
    }

    public function test_order_resource_includes_shipment_when_loaded(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        $order->shipment()->create([
            'status' => 'delivered',
            'carrier' => 'FedEx',
            'tracking_number' => 'TRK987654321',
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
        ]);

        $resource = \App\Http\Resources\OrderResource::make($order->load('shipment'));
        $data = $resource->resolve();

        $this->assertArrayHasKey('shipment', $data);
        $this->assertSame('delivered', $data['shipment']['statusCode']);
        $this->assertSame('FedEx', $data['shipment']['carrier']);
        $this->assertSame('TRK987654321', $data['shipment']['trackingNumber']);
    }

    public function test_shipment_created_with_order_shipping_fee_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'shipping' => 9.95,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'UPS',
            ])
            ->assertCreated()
            ->assertJsonPath('data.shippingFee', 9.95);
    }
}