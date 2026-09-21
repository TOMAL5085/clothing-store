<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;

class RefundCompletedNotification extends OrderNotification
{
    public function __construct(Order $order, Refund $refund)
    {
        $ref = $refund->provider_reference ? " Reference: {$refund->provider_reference}" : '';
        parent::__construct(
            $order,
            'Refund Completed',
            "Your refund of {$order->currency} " . number_format((float) $refund->amount, 2) . " for order #{$order->number} has been completed.{$ref}",
            'refund_completed',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'refund_completed';
    }
}