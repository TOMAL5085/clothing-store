<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SslCommerzGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sslcommerz';
    }

    public function initiate(Order $order, Payment $payment): array
    {
        $storeId = (string) config('payments.sslcommerz.store_id');
        $storePassword = (string) config('payments.sslcommerz.store_password');
        if ($storeId === '' || $storePassword === '') {
            throw ValidationException::withMessages(['payment' => 'SSLCOMMERZ is not configured.']);
        }

        $address = $order->shippingAddress;
        $base = rtrim((string) config('app.url'), '/');
        $payload = [
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'total_amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => strtoupper((string) config('payments.sslcommerz.currency', 'USD')),
            'tran_id' => $payment->reference,
            'success_url' => $base.'/api/v1/payments/sslcommerz/success',
            'fail_url' => $base.'/api/v1/payments/sslcommerz/fail',
            'cancel_url' => $base.'/api/v1/payments/sslcommerz/cancel',
            'ipn_url' => $base.'/api/v1/payments/sslcommerz/ipn',
            'cus_name' => trim(($address?->first_name ?? 'Customer').' '.($address?->last_name ?? '')),
            'cus_email' => $address?->email ?? 'orders@example.test',
            'cus_add1' => $address?->address ?? 'Address',
            'cus_city' => $address?->city ?? 'City',
            'cus_postcode' => $address?->postal_code ?? '0000',
            'cus_country' => $address?->country ?? 'Bangladesh',
            'cus_phone' => $address?->phone ?: '00000000000',
            'shipping_method' => 'YES',
            'ship_name' => trim(($address?->first_name ?? 'Customer').' '.($address?->last_name ?? '')),
            'ship_add1' => $address?->address ?? 'Address',
            'ship_city' => $address?->city ?? 'City',
            'ship_postcode' => $address?->postal_code ?? '0000',
            'ship_country' => $address?->country ?? 'Bangladesh',
            'product_name' => 'JAAJ order '.$order->number,
            'product_category' => 'clothing',
            'product_profile' => 'general',
            'value_a' => $order->number,
            'value_b' => $order->checkout_token,
        ];

        try {
            $response = Http::asForm()
                ->post($this->sessionUrl(), $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            Log::warning('sslcommerz.session_failed', ['order' => $order->number, 'status' => $exception->response?->status()]);
            throw ValidationException::withMessages(['payment' => 'Payment initialization failed.']);
        }

        $redirect = $response['GatewayPageURL'] ?? $response['gatewayPageURL'] ?? null;
        if (! is_string($redirect) || $redirect === '' || ($response['status'] ?? '') !== 'SUCCESS') {
            throw ValidationException::withMessages(['payment' => 'Payment initialization failed.']);
        }

        $payment->update([
            'payload' => ['session' => ['status' => $response['status'] ?? null]],
        ]);

        return [
            'redirect_url' => $redirect,
            'client_secret' => null,
            'reference' => $payment->reference,
            'status' => 'pending',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status:string, tran_id:?string, val_id:?string, amount:?string, currency:?string}
     */
    public function validateIpn(array $payload): array
    {
        $tranId = $payload['tran_id'] ?? null;
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $valId = $payload['val_id'] ?? null;

        if (! is_string($tranId) || $tranId === '') {
            return ['status' => 'INVALID', 'tran_id' => null, 'val_id' => null, 'amount' => null, 'currency' => null];
        }

        if (in_array($status, ['FAILED', 'CANCELLED', 'UNATTEMPTED', 'EXPIRED'], true)) {
            return [
                'status' => $status === 'CANCELLED' ? 'CANCELLED' : 'FAILED',
                'tran_id' => $tranId,
                'val_id' => is_string($valId) ? $valId : null,
                'amount' => isset($payload['amount']) ? (string) $payload['amount'] : null,
                'currency' => isset($payload['currency']) ? (string) $payload['currency'] : null,
            ];
        }

        if (! is_string($valId) || $valId === '') {
            return ['status' => 'INVALID', 'tran_id' => $tranId, 'val_id' => null, 'amount' => null, 'currency' => null];
        }

        $storeId = (string) config('payments.sslcommerz.store_id');
        $storePassword = (string) config('payments.sslcommerz.store_password');

        try {
            $response = Http::get($this->validationUrl(), [
                'val_id' => $valId,
                'store_id' => $storeId,
                'store_passwd' => $storePassword,
                'format' => 'json',
            ])->throw()->json();
        } catch (RequestException $exception) {
            Log::warning('sslcommerz.validation_failed', ['tran_id' => $tranId, 'status' => $exception->response?->status()]);

            return ['status' => 'INVALID', 'tran_id' => $tranId, 'val_id' => $valId, 'amount' => null, 'currency' => null];
        }

        $validStatus = strtoupper((string) ($response['status'] ?? ''));
        if (! in_array($validStatus, ['VALID', 'VALIDATED'], true)) {
            return ['status' => 'INVALID', 'tran_id' => $tranId, 'val_id' => $valId, 'amount' => null, 'currency' => null];
        }

        return [
            'status' => 'VALID',
            'tran_id' => $tranId,
            'val_id' => $valId,
            'amount' => isset($response['currency_amount']) ? (string) $response['currency_amount'] : (string) ($response['amount'] ?? ''),
            'currency' => (string) ($response['currency'] ?? $payload['currency'] ?? ''),
        ];
    }

    private function sessionUrl(): string
    {
        return $this->sandbox()
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    }

    private function validationUrl(): string
    {
        return $this->sandbox()
            ? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
            : 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
    }

    private function sandbox(): bool
    {
        return (bool) config('payments.sslcommerz.sandbox', true);
    }
}
