<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnRequest;

class ReturnApprovedNotification extends OrderNotification
{
    public function __construct(Order $order, ReturnRequest $return)
    {
        parent::__construct(
            $order,
            'Return Approved',
            "Your return request for order #{$order->number} has been approved. Please ship the items back to us.",
            'return_approved',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'return_approved';
    }
}