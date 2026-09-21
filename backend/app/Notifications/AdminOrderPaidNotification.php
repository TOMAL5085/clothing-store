<?php

namespace App\Notifications;

use App\Models\Order;

class AdminOrderPaidNotification extends AdminOrderNotification
{
    public function __construct(Order $order)
    {
        parent::__construct(
            $order,
            'Payment Confirmed',
            "Payment for order #{$order->number} has been confirmed.",
            'admin_order_paid',
            config('app.frontend_url', 'http://localhost:5173') . "/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_order_paid';
    }
}