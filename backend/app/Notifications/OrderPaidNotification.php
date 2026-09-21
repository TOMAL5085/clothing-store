<?php

namespace App\Notifications;

use App\Models\Order;

class OrderPaidNotification extends OrderNotification
{
    public function __construct(Order $order)
    {
        parent::__construct(
            $order,
            'Payment Confirmed',
            "Payment for order #{$order->number} has been confirmed. Your order is now being processed.",
            'order_paid',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'order_paid';
    }
}