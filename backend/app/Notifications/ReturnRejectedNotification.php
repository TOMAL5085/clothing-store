<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnRequest;

class ReturnRejectedNotification extends OrderNotification
{
    public function __construct(Order $order, ReturnRequest $return)
    {
        $reason = $return->admin_reason ? " Reason: {$return->admin_reason}" : '';
        parent::__construct(
            $order,
            'Return Request Rejected',
            "Your return request for order #{$order->number} has been rejected.{$reason}",
            'return_rejected',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'return_rejected';
    }
}