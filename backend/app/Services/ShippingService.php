<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Couriers\CourierGateway;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Services\Couriers\CourierService;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function __construct(
        private readonly CourierService $courierService,
    ) {
    }

    public function createShipment(Order $order, array $data): Shipment
    {
        if ($order->shipment()->exists()) {
            throw ValidationException::withMessages([
                'shipment' => 'This order already has a shipment.',
            ]);
        }

        // Determine carrier from request data or fallback to courier gateway name
        $requestedCarrier = $data['carrier'] ?? null;
        $requestedTrackingNumber = $data['tracking_number'] ?? null;
        $requestedTrackingReference = $data['tracking_reference'] ?? null;

        // Create internal shipment record first
        $shipment = $order->shipment()->create([
            'status' => 'pending',
            'carrier' => $requestedCarrier,
            'tracking_number' => $requestedTrackingNumber,
            'tracking_reference' => $requestedTrackingReference,
            'shipping_fee' => $data['shipping_fee'] ?? $order->shipping,
            'estimated_delivery_at' => $data['estimated_delivery_at'] ?? null,
        ]);

        // Create initial tracking event for internal creation
        $this->createEvent($shipment, 'pending', null, 'Shipment created.');

        // Create courier shipment via configured provider
        $courierResult = $this->courierService->gateway()->createShipment($order, $shipment);

        // Update internal shipment with courier response, preserving admin-provided values
        $shipment->update([
            'carrier' => $requestedCarrier ?? $courierResult['carrier'] ?? $this->courierService->gateway()->name(),
            'tracking_number' => $requestedTrackingNumber ?? $courierResult['tracking_number'] ?? $shipment->tracking_number,
            'tracking_reference' => $requestedTrackingReference ?? $courierResult['carrier_reference'] ?? $shipment->tracking_reference,
            'carrier_reference' => $courierResult['carrier_reference'] ?? $shipment->carrier_reference,
            'estimated_delivery_at' => $courierResult['estimated_delivery_at'] ?? $shipment->estimated_delivery_at,
            'status' => $courierResult['status'] ?? $shipment->status,
        ]);

        // Create event for courier shipment creation if status changed
        if (($courierResult['status'] ?? 'pending') !== 'pending') {
            $this->createEvent($shipment, $courierResult['status'], null, 'Courier shipment created.');
        }

        return $shipment->refresh();
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

    /**
     * Synchronize an existing Shipment with the latest courier status.
     *
     * @param  \App\Models\Shipment  $shipment
     * @return void
     */
    public function syncShipmentStatus(Shipment $shipment): void
    {
        $rawStatus = $this->courierService->gateway()->getStatus($shipment);
        $mappedStatus = $this->courierService->gateway()->mapExternalStatusToInternal($rawStatus);

        // Check if mapped status is a valid internal status
        if (! in_array($mappedStatus, Shipment::STATUSES, true)) {
            // Unknown status - do not modify Shipment or create event
            return;
        }

        // If status is the same as current, do nothing (idempotent)
        if ($shipment->status === $mappedStatus) {
            return;
        }

        // Status changed - reuse existing ShippingService transition logic
        // This will validate the transition, update the Shipment, and create exactly one event
        $this->updateShipment($shipment, ['status' => $mappedStatus]);
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