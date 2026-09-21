<?php

namespace App\Notifications;

use App\Models\Order;

class OrderPlacedNotification extends OrderNotification
{
    public function __construct(Order $order)
    {
        parent::__construct(
            $order,
            'Order Placed Successfully',
            "Your order #{$order->number} has been placed and is now being processed.",
            'order_placed',
            config('app.frontend_url', 'http://localhost:5173') . "/order/success/{$order->number}?checkout_token={$order->checkout_token}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'order_placed';
    }
}