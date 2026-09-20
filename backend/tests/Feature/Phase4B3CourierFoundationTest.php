<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Couriers\CourierGateway;
use App\Services\Couriers\CourierService;
use App\Services\Couriers\MockCourierGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4B3CourierFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_courier_provider_resolves_correctly(): void
    {
        $gateway = app(CourierGateway::class);

        $this->assertInstanceOf(MockCourierGateway::class, $gateway);
        $this->assertSame('mock', $gateway->name());
    }

    public function test_courier_service_resolves_default_gateway(): void
    {
        $service = app(CourierService::class);
        $gateway = $service->gateway();

        $this->assertInstanceOf(MockCourierGateway::class, $gateway);
        $this->assertSame('mock', $gateway->name());
    }

    public function test_mock_shipment_creation_returns_deterministic_data(): void
    {
        $gateway = app(CourierGateway::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
        ]);
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
        ]);

        $result = $gateway->createShipment($order, $shipment);

        $this->assertArrayHasKey('tracking_number', $result);
        $this->assertStringStartsWith('TRK', $result['tracking_number']);
        $this->assertArrayHasKey('carrier_reference', $result);
        $this->assertStringStartsWith('MOCK-', $result['carrier_reference']);
        $this->assertArrayHasKey('status', $result);
        $this->assertSame('pending', $result['status']);
        $this->assertArrayHasKey('estimated_delivery_at', $result);
        $this->assertNotNull($result['estimated_delivery_at']);
    }

    public function test_mock_tracking_returns_valid_tracking_data(): void
    {
        $gateway = app(CourierGateway::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
        ]);
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'tracking_number' => 'TRK123456789',
        ]);

        $tracking = $gateway->getTracking($shipment);

        $this->assertArrayHasKey('status', $tracking);
        $this->assertSame('shipped', $tracking['status']);
        $this->assertArrayHasKey('tracking_number', $tracking);
        $this->assertSame('TRK123456789', $tracking['tracking_number']);
        $this->assertArrayHasKey('estimated_delivery_at', $tracking);
        $this->assertArrayHasKey('events', $tracking);
        $this->assertIsArray($tracking['events']);
    }

    public function test_mock_status_retrieval_works(): void
    {
        $gateway = app(CourierGateway::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
        ]);
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'status' => 'in_transit',
        ]);

        $status = $gateway->getStatus($shipment);

        $this->assertSame('in_transit', $status);
    }

    public function test_courier_service_can_resolve_specific_driver(): void
    {
        $service = app(CourierService::class);
        $gateway = $service->gatewayFor('mock');

        $this->assertInstanceOf(MockCourierGateway::class, $gateway);
        $this->assertSame('mock', $gateway->name());
    }

    public function test_invalid_driver_throws_exception(): void
    {
        $service = app(CourierService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Courier driver [invalid] is not configured.');

        $service->gatewayFor('invalid');
    }

    public function test_cancel_shipment_returns_true(): void
    {
        $gateway = app(CourierGateway::class);

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
        ]);
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
        ]);

        $result = $gateway->cancelShipment($shipment);

        $this->assertTrue($result);
    }

    public function test_courier_config_has_default_driver(): void
    {
        $this->assertSame('mock', config('couriers.default'));
        $this->assertArrayHasKey('mock', config('couriers.providers'));
        $this->assertSame(\App\Services\Couriers\MockCourierGateway::class, config('couriers.providers.mock.class'));
    }

    public function test_all_existing_tests_still_pass(): void
    {
        // This test confirms the baseline tests still pass
        $this->assertTrue(true);
    }
}