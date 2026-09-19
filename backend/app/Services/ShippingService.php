<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
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

        return $shipment;
    }

    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
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

        return $shipment->refresh();
    }

    public function getShipmentForOrder(Order $order): ?Shipment
    {
        return $order->shipment;
    }
}