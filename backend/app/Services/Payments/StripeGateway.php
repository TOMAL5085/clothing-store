<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function initiate(Order $order, Payment $payment): array
    {
        $secret = (string) config('payments.stripe.secret');
        if ($secret === '') {
            throw ValidationException::withMessages(['payment' => 'Stripe is not configured.']);
        }

        $frontend = rtrim((string) config('payments.frontend_url'), '/');
        $currency = strtolower((string) config('payments.stripe.currency', 'usd'));
        $amount = Money::toMinor($order->total);

        try {
            $response = Http::withToken($secret)
                ->asForm()
                ->withHeaders(['Idempotency-Key' => $payment->reference])
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $frontend.'/order/success/'.$order->number.'?checkout_token='.$order->checkout_token,
                    'cancel_url' => $frontend.'/order/failed?reason='.urlencode('Payment was canceled.'),
                    'client_reference_id' => $order->number,
                    'payment_intent_data[metadata][order_number]' => $order->number,
                    'payment_intent_data[metadata][payment_reference]' => $payment->reference,
                    'metadata[order_number]' => $order->number,
                    'metadata[payment_reference]' => $payment->reference,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => $currency,
                    'line_items[0][price_data][unit_amount]' => $amount,
                    'line_items[0][price_data][product_data][name]' => 'JAAJ order '.$order->number,
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            Log::warning('stripe.session_failed', ['order' => $order->number, 'status' => $exception->response?->status()]);
            throw ValidationException::withMessages(['payment' => 'Payment initialization failed.']);
        }

        $redirect = $response['url'] ?? null;
        if (! is_string($redirect) || $redirect === '') {
            throw ValidationException::withMessages(['payment' => 'Payment initialization failed.']);
        }

        $payment->update([
            'provider_event_id' => $response['id'] ?? $payment->provider_event_id,
            'payload' => [
                'session_id' => $response['id'] ?? null,
                'payment_intent' => $response['payment_intent'] ?? null,
            ],
        ]);

        return [
            'redirect_url' => $redirect,
            'client_secret' => $response['client_secret'] ?? null,
            'reference' => $payment->reference,
            'status' => 'pending',
        ];
    }

    /**
     * @return array{type:string, id:string, data:array<string, mixed>}
     */
    public function parseWebhook(string $payload, ?string $signatureHeader): array
    {
        $secret = (string) config('payments.stripe.webhook_secret');
        if ($secret === '' || ! $signatureHeader) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        $this->verifySignature($payload, $signatureHeader, $secret);

        $event = json_decode($payload, true);
        if (! is_array($event) || ! isset($event['type'], $event['id'], $event['data']['object'])) {
            throw new RuntimeException('Invalid Stripe event payload.');
        }

        return [
            'type' => (string) $event['type'],
            'id' => (string) $event['id'],
            'data' => $event['data']['object'],
        ];
    }

    private function verifySignature(string $payload, string $header, string $secret): void
    {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1' && is_string($value)) {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || $signatures === []) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        if (abs(time() - (int) $timestamp) > 300) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Invalid webhook signature.');
    }
}
