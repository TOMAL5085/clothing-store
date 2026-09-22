<?php

namespace App\Notifications;

use App\Models\Order;

class AdminNewOrderNotification extends AdminOrderNotification
{
    public function __construct(Order $order)
    {
        $customerName = $order->user?->name ?? 'Guest';
        parent::__construct(
            $order,
            'New Order Received',
            "A new order #{$order->number} has been placed by {$customerName}.",
            'admin_new_order',
            config('app.frontend_url', 'http://localhost:5173')."/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_new_order';
    }
}
