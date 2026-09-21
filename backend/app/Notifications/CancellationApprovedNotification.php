<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\CancellationRequest;

class CancellationApprovedNotification extends OrderNotification
{
    public function __construct(Order $order, CancellationRequest $cancellation)
    {
        parent::__construct(
            $order,
            'Cancellation Approved',
            "Your cancellation request for order #{$order->number} has been approved. The order is being cancelled.",
            'cancellation_approved',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'cancellation_approved';
    }
}