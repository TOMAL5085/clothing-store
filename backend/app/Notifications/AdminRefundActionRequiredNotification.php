<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;

class AdminRefundActionRequiredNotification extends AdminOrderNotification
{
    public function __construct(Order $order, Refund $refund)
    {
        parent::__construct(
            $order,
            'Refund Requires Action',
            "A refund of {$order->currency} " . number_format((float) $refund->amount, 2) . " for order #{$order->number} requires admin action. Status: {$refund->status}, Reason: {$refund->reason}",
            'admin_refund_action_required',
            config('app.frontend_url', 'http://localhost:5173') . "/admin/orders/{$order->number}",
        );
    }

    protected function getNotificationType(): string
    {
        return 'admin_refund_action_required';
    }
}