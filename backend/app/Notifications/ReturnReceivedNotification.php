<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnRequest;

class ReturnReceivedNotification extends OrderNotification
{
    public function __construct(Order $order, ReturnRequest $return)
    {
        parent::__construct(
            $order,
            'Return Received',
            "We have received your return for order #{$order->number}. Your refund is being processed.",
            'return_received',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'return_received';
    }
}