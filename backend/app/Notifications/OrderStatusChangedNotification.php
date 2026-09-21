<?php

namespace App\Notifications;

use App\Models\Order;

class OrderStatusChangedNotification extends OrderNotification
{
    public function __construct(Order $order, string $oldStatus, string $newStatus)
    {
        $statusMessages = [
            'confirmed' => 'Your order has been confirmed and is being prepared.',
            'processing' => 'Your order is now being processed.',
            'shipped' => 'Your order has been shipped!',
            'delivered' => 'Your order has been delivered.',
            'cancelled' => 'Your order has been cancelled.',
        ];

        $message = $statusMessages[$newStatus] ?? "Your order status has been updated to {$newStatus}.";

        parent::__construct(
            $order,
            'Order Status Update: ' . ucfirst(str_replace('_', ' ', $newStatus)),
            "Order #{$order->number}: {$message}",
            'order_status_changed',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'order_status_changed';
    }
}