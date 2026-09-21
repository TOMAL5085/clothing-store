<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;

class RefundFailedNotification extends OrderNotification
{
    public function __construct(Order $order, Refund $refund)
    {
        $reason = $refund->failure_reason ? " Reason: {$refund->failure_reason}" : '';
        parent::__construct(
            $order,
            'Refund Failed',
            "Your refund of {$order->currency} " . number_format((float) $refund->amount, 2) . " for order #{$order->number} could not be processed.{$reason} Please contact support.",
            'refund_failed',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'refund_failed';
    }
}