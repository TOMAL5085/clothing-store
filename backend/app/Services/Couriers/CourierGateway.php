<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Models\Shipment;

interface CourierGateway
{
    public function name(): string;

    /**
     * Create a shipment with the courier.
     *
     * @return array{tracking_number:string, carrier_reference:?string, status:string, estimated_delivery_at:?string}
     */
    public function createShipment(Order $order, Shipment $shipment): array;

    /**
     * Get tracking information for a shipment.
     *
     * @return array{status:string, tracking_number:?string, estimated_delivery_at:?string, events:array<int, array{status:string, description:?string, occurred_at:string}>}
     */
    public function getTracking(Shipment $shipment): array;

    /**
     * Get the current status of a shipment.
     */
    public function getStatus(Shipment $shipment): string;

    /**
     * Cancel a shipment if supported by the courier.
     */
    public function cancelShipment(Shipment $shipment): bool;
}