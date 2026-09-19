<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function createShipment(Order $order, array $data): Shipment
    {
        if ($order->shipment()->exists()) {
            throw ValidationException::withMessages([
                'shipment' => 'This order already has a shipment.',
            ]);
        }

        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => $data['carrier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'tracking_reference' => $data['tracking_reference'] ?? null,
            'shipping_fee' => $data['shipping_fee'] ?? $order->shipping,
            'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
        ]);

        // Create initial tracking event
        $this->createEvent($shipment, 'pending', null, 'Shipment created.');

        return $shipment;
    }

    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
        $oldStatus = $shipment->status;

        if (isset($data['status']) && $data['status'] !== $shipment->status) {
            if (! $shipment->canTransitionTo($data['status'])) {
                throw ValidationException::withMessages([
                    'status' => "Cannot transition from {$shipment->status} to {$data['status']}.",
                ]);
            }

            $now = now();
            if ($data['status'] === 'shipped') {
                $shipment->shipped_at = $now;
            } elseif ($data['status'] === 'delivered') {
                $shipment->delivered_at = $now;
            }
        }

        $shipment->fill($data);
        $shipment->save();

        // Create tracking event if status changed
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $this->createEvent($shipment, $data['status'], $data['location'] ?? null, $data['description'] ?? null);
        }

        return $shipment->refresh();
    }

    private function createEvent(Shipment $shipment, string $status, ?string $location, ?string $description): void
    {
        $descriptions = [
            'pending' => 'Shipment created and pending processing.',
            'processing' => 'Shipment is being processed.',
            'ready_to_ship' => 'Shipment is ready to be shipped.',
            'shipped' => 'Shipment has been shipped.',
            'in_transit' => 'Shipment is in transit.',
            'out_for_delivery' => 'Shipment is out for delivery.',
            'delivered' => 'Shipment has been delivered.',
            'failed_delivery' => 'Delivery attempt failed.',
        ];

        ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $status,
            'location' => $location,
            'description' => $description ?? $descriptions[$status] ?? "Status updated to {$status}.",
            'occurred_at' => now(),
        ]);
    }

    public function getShipmentForOrder(Order $order): ?Shipment
    {
        return $order->shipment;
    }
}