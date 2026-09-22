<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Shipment;

class AdminShipmentProblemNotification extends AdminOrderNotification
{
    public function __construct(Order $order, Shipment $shipment)
    {
        $carrier = $shipment->carrier !== null ? $shipment->carrier : 'N/A';
        $tracking = $shipment->tracking_number !== null ? $shipment->tracking_number : 'N/A';

        parent::__construct(
            $order,
            'Shipment Issue Detected',
            "Order #{$order->number} has a shipment issue. Current status: {$shipment->status}. Carrier: {$carrier}. Tracking: {$tracking}",
            'admin_shipment_problem',
            config('app.frontend_url', 'http://localhost:5173')."/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_shipment_problem';
    }
}
