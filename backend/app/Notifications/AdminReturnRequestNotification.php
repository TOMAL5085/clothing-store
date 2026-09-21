<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ReturnRequest;

class AdminReturnRequestNotification extends AdminOrderNotification
{
    public function __construct(Order $order, ReturnRequest $return)
    {
        $itemCount = $return->items->count();
        $itemText = $itemCount === 1 ? '1 item' : "{$itemCount} items";

        parent::__construct(
            $order,
            'Return Request Received',
            "Customer {$order->user->name} has requested a return for order #{$order->number} ({$itemText}). Reason: {$return->reason}",
            'admin_return_requested',
            config('app.frontend_url', 'http://localhost:5173') . "/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_return_requested';
    }
}