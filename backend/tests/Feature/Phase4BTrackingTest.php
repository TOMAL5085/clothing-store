<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4BTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipment_events_created_on_creation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'carrier' => 'DHL',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $order->shipment->id,
            'status' => 'pending',
            'description' => 'Shipment created.',
        ]);
    }

    public function test_shipment_events_created_on_status_change(): void
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

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ])
            ->assertOk();

        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => 'ready_to_ship',
            'description' => 'Shipment is ready to be shipped.',
        ]);
    }

    public function test_shipment_events_chronological_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        
        // Create shipment via ShippingService to trigger initial event
        $shippingService = app(\App\Services\ShippingService::class);
        $shipment = $shippingService->createShipment($order, ['carrier' => 'DHL']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'processing',
            ])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'shipped',
            ])
            ->assertOk();

        $events = ShipmentEvent::where('shipment_id', $shipment->id)
            ->orderBy('occurred_at')
            ->get();

        $this->assertCount(4, $events); // pending (created) + processing + ready_to_ship + shipped
        $this->assertSame('pending', $events[0]->status);
        $this->assertSame('processing', $events[1]->status);
        $this->assertSame('ready_to_ship', $events[2]->status);
        $this->assertSame('shipped', $events[3]->status);
    }

    public function test_customer_can_view_tracking_timeline(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
        ]);
        $shipment->events()->create([
            'status' => 'pending',
            'description' => 'Shipment created.',
            'occurred_at' => now()->subDays(3),
        ]);
        $shipment->events()->create([
            'status' => 'shipped',
            'description' => 'Shipment has been shipped.',
            'occurred_at' => now()->subDays(2),
        ]);
        $shipment->events()->create([
            'status' => 'in_transit',
            'description' => 'Shipment is in transit.',
            'occurred_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}/tracking");

        $response->assertOk()
            ->assertJsonPath('data.shipment.events.0.statusCode', 'pending')
            ->assertJsonPath('data.shipment.events.1.statusCode', 'shipped')
            ->assertJsonPath('data.shipment.events.2.statusCode', 'in_transit');
    }

    public function test_customer_cannot_view_another_customer_tracking(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
        ]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}/tracking")
            ->assertForbidden();
    }

    public function test_unauthenticated_customer_rejected(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);

        $this->getJson("/api/v1/orders/{$order->number}/tracking")
            ->assertUnauthorized();
    }

    public function test_admin_can_view_tracking_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
        ]);
        $shipment->events()->create([
            'status' => 'shipped',
            'description' => 'Shipped from warehouse.',
            'occurred_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->number}");

        $response->assertOk()
            ->assertJsonPath('data.shipment.events.0.statusCode', 'shipped');
    }

    public function test_no_duplicate_events_for_same_status(): void
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

        // First transition
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ])
            ->assertOk();

        // Second transition to same status should not create duplicate
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ])
            ->assertOk();

        $eventCount = ShipmentEvent::where('shipment_id', $shipment->id)
            ->where('status', 'ready_to_ship')
            ->count();

        $this->assertSame(1, $eventCount);
    }

    public function test_events_include_occurred_at_timestamp(): void
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

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->number}/shipment", [
                'status' => 'ready_to_ship',
            ])
            ->assertOk();

        $event = ShipmentEvent::where('shipment_id', $shipment->id)
            ->where('status', 'ready_to_ship')
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->occurred_at);
        $this->assertTrue($event->occurred_at->diffInSeconds(now()) < 10);
    }

    public function test_tracking_endpoint_includes_order_and_shipment_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
            'number' => 'JAAJ-123456',
        ]);
        $shipment = $order->shipment()->create([
            'status' => 'in_transit',
            'carrier' => 'DHL',
            'tracking_number' => 'TRK123456789',
            'estimated_delivery_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}/tracking");

        $response->assertOk()
            ->assertJsonPath('data.id', 'JAAJ-123456')
            ->assertJsonPath('data.shipment.carrier', 'DHL')
            ->assertJsonPath('data.shipment.trackingNumber', 'TRK123456789')
            ->assertJsonPath('data.shipment.statusCode', 'in_transit')
            ->assertJsonPath('data.shipment.estimatedDeliveryAt', function ($date) {
                return $date !== null;
            });
    }
}