<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnRequest;

class ReturnRequestedNotification extends OrderNotification
{
    public function __construct(Order $order, ReturnRequest $return)
    {
        $itemCount = $return->items->count();
        $itemText = $itemCount === 1 ? '1 item' : "{$itemCount} items";

        parent::__construct(
            $order,
            'Return Request Received',
            "Your return request for order #{$order->number} ({$itemText}) has been received and is pending review. Reason: {$return->reason}",
            'return_requested',
            config('app.frontend_url', 'http://localhost:5173') . "/account?tab=orders",
        );
    }

    protected function getNotificationType(): string
    {
        return 'return_requested';
    }
}