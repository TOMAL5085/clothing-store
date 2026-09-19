<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function charge(array $payment, float $amount): array
    {
        $digits = preg_replace('/\D+/', '', $payment['card_number']);

        if (strlen($digits) !== 16) {
            throw ValidationException::withMessages(['payment.card_number' => 'Card number must be 16 digits.']);
        }

        if (str_ends_with($digits, '0000')) {
            throw ValidationException::withMessages(['payment.card_number' => 'Your card was declined by the issuing bank.']);
        }

        return [
            'provider' => 'demo',
            'status' => 'paid',
            'reference' => 'demo_'.Str::lower(Str::random(24)),
            'amount' => $amount,
            'currency' => strtoupper((string) config('payments.store_currency', 'USD')),
            'card_last_four' => substr($digits, -4),
            'method' => 'card',
        ];
    }
}
