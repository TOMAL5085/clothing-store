<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    /**
     * @return array{redirect_url:?string, client_secret:?string, reference:string, status:string}
     */
    public function initiate(Order $order, Payment $payment): array;
}
