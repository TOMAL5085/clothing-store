<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;

class RefundProcessingNotification extends OrderNotification
{
    public function __construct(Order $order, Refund $refund)
    {
        parent::__construct(
            $order,
            'Refund Processing',
            "Your refund of {$order->currency} " . number_format((float) $refund->amount, 2) . " for order #{$order->number} is now being processed by the payment provider.",
            'refund_processing',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'refund_processing';
    }
}