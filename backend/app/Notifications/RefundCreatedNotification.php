<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;

class RefundCreatedNotification extends OrderNotification
{
    public function __construct(Order $order, Refund $refund)
    {
        parent::__construct(
            $order,
            'Refund Initiated',
            "A refund of {$order->currency} " . number_format((float) $refund->amount, 2) . " for order #{$order->number} has been initiated. Reason: {$refund->reason}",
            'refund_created',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'refund_created';
    }
}