<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Shipment;

class ShipmentStatusChangedNotification extends OrderNotification
{
    public function __construct(Order $order, Shipment $shipment, string $oldStatus, string $newStatus)
    {
        $statusMessages = [
            'pending' => 'Your shipment is pending.',
            'processing' => 'Your shipment is being processed.',
            'ready_to_ship' => 'Your shipment is ready to be shipped.',
            'shipped' => 'Your order has been shipped!',
            'in_transit' => 'Your shipment is in transit.',
            'out_for_delivery' => 'Your shipment is out for delivery.',
            'delivered' => 'Your shipment has been delivered.',
            'failed_delivery' => 'Delivery attempt failed. The courier will try again.',
        ];

        $message = $statusMessages[$newStatus] ?? "Your shipment status has been updated to {$newStatus}.";

        if ($shipment->tracking_number) {
            $message .= " Tracking: {$shipment->tracking_number}";
        }

        if ($shipment->carrier) {
            $message .= " Carrier: {$shipment->carrier}";
        }

        parent::__construct(
            $order,
            'Shipment Update: ' . ucfirst(str_replace('_', ' ', $newStatus)),
            "Order #{$order->number}: {$message}",
            'shipment_status_changed',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'shipment_status_changed';
    }
}