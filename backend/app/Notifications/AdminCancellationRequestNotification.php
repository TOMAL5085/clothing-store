<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\CancellationRequest;

class AdminCancellationRequestNotification extends AdminOrderNotification
{
    public function __construct(Order $order, CancellationRequest $cancellation)
    {
        parent::__construct(
            $order,
            'Cancellation Request Received',
            "Customer {$order->user->name} has requested cancellation for order #{$order->number}. Reason: {$cancellation->reason}",
            'admin_cancellation_requested',
            config('app.frontend_url', 'http://localhost:5173') . "/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_cancellation_requested';
    }
}