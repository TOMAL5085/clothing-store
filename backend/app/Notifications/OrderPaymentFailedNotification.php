<?php

namespace App\Notifications;

use App\Models\Order;

class OrderPaymentFailedNotification extends OrderNotification
{
    public function __construct(Order $order, string $reason = 'Payment could not be processed')
    {
        parent::__construct(
            $order,
            'Payment Failed',
            "Payment for order #{$order->number} failed: {$reason}. Please try again or use a different payment method.",
            'order_payment_failed',
            config('app.frontend_url', 'http://localhost:5173') . "/order/failed?reason=" . urlencode($reason),
        );
    }

    protected function getNotificationType(): string
    {
        return 'order_payment_failed';
    }
}