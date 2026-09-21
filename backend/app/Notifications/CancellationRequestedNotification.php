<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\CancellationRequest;

class CancellationRequestedNotification extends OrderNotification
{
    public function __construct(Order $order, CancellationRequest $cancellation)
    {
        parent::__construct(
            $order,
            'Cancellation Request Received',
            "Your cancellation request for order #{$order->number} has been received and is pending review. Reason: {$cancellation->reason}",
            'cancellation_requested',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'cancellation_requested';
    }
}