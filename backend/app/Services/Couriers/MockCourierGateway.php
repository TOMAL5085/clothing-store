<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MockCourierGateway implements CourierGateway
{
    public function name(): string
    {
        return 'mock';
    }

    public function createShipment(Order $order, Shipment $shipment): array
    {
        return [
            'tracking_number' => 'TRK'.Str::upper(Str::random(10)),
            'carrier_reference' => 'MOCK-'.Str::upper(Str::random(8)),
            'status' => 'pending',
            'estimated_delivery_at' => Carbon::now()->addDays(rand(3, 7))->toISOString(),
        ];
    }

    public function getTracking(Shipment $shipment): array
    {
        $events = $shipment->events->map(function ($event) {
            return [
                'status' => $event->status,
                'description' => $event->description,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ];
        })->values()->all();

        return [
            'status' => $shipment->status,
            'tracking_number' => $shipment->tracking_number,
            'estimated_delivery_at' => $shipment->estimated_delivery_at?->toISOString(),
            'events' => $events,
        ];
    }

    public function getStatus(Shipment $shipment): string
    {
        return $shipment->status;
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        return true;
    }
}