<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\CancellationRequest;

class CancellationCompletedNotification extends OrderNotification
{
    public function __construct(Order $order, CancellationRequest $cancellation)
    {
        $refundInfo = $cancellation->refund
            ? " A refund of {$order->currency} " . number_format((float) $cancellation->refund->amount, 2) . " has been initiated."
            : '';

        parent::__construct(
            $order,
            'Order Cancelled',
            "Order #{$order->number} has been cancelled.{$refundInfo}",
            'cancellation_completed',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'cancellation_completed';
    }
}