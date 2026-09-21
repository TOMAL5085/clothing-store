<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\CancellationRequest;

class CancellationRejectedNotification extends OrderNotification
{
    public function __construct(Order $order, CancellationRequest $cancellation)
    {
        $reason = $cancellation->admin_reason ? " Reason: {$cancellation->admin_reason}" : '';
        parent::__construct(
            $order,
            'Cancellation Request Rejected',
            "Your cancellation request for order #{$order->number} has been rejected.{$reason}",
            'cancellation_rejected',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'cancellation_rejected';
    }
}