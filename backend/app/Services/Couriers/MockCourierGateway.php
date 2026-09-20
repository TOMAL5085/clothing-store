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
            'carrier' => 'Mock Courier',
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
        return $this->mapExternalStatusToInternal($shipment->status);
    }

    /**
     * Map an external courier status to an internal shipment status.
     *
     * The mock gateway's status is already an internal status, so we return it as-is.
     * Real providers should implement proper mapping here.
     *
     * @param  string $rawStatus The raw status from the courier provider
     * @return string The mapped internal shipment status
     */
    public function mapExternalStatusToInternal(string $rawStatus): string
    {
        // The mock returns internal statuses directly, so identity mapping
        // Real providers would map external statuses like "out_for_delivery" → "out_for_delivery", etc.
        $internalStatuses = [
            'pending',
            'processing',
            'ready_to_ship',
            'shipped',
            'in_transit',
            'out_for_delivery',
            'delivered',
            'failed_delivery',
        ];

        // Normalize: replace underscores with spaces for comparison, then title-case
        // But for the mock, just check if it's a known internal status
        foreach ($internalStatuses as $status) {
            if ($rawStatus === $status) {
                return $rawStatus;
            }
            // Handle common variations: "out for delivery" → "out_for_delivery"
            $normalized = str_replace('_', ' ', $rawStatus);
            if (trim($normalized) === $status) {
                return $status;
            }
            // Handle title-case variations
            if (ucwords(str_replace('_', ' ', $rawStatus)) === ucwords(str_replace('_', ' ', $status))) {
                return $status;
            }
        }

        // Unknown status: return as-is to avoid changing the shipment
        // The controller will handle this gracefully
        return $rawStatus;
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        return true;
    }
}