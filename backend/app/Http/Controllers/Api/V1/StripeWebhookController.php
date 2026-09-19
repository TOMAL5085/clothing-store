<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderFulfillmentService;
use App\Services\Payments\StripeGateway;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly OrderFulfillmentService $fulfillment,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->stripe->parseWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (AccessDeniedHttpException|\RuntimeException $exception) {
            Log::warning('stripe.webhook_rejected');

            return response('invalid signature', 400);
        }

        $object = $event['data'];
        $reference = $object['metadata']['payment_reference'] ?? $object['client_reference_id'] ?? null;
        $sessionId = $object['id'] ?? null;

        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where(function ($query) use ($reference, $sessionId) {
                if (is_string($reference)) {
                    $query->orWhere('reference', $reference);
                }
                if (is_string($sessionId)) {
                    $query->orWhere('provider_event_id', $sessionId)
                        ->orWhere('payload->session_id', $sessionId);
                }
                $orderNumber = $object['client_reference_id'] ?? $object['metadata']['order_number'] ?? null;
                if (is_string($orderNumber)) {
                    $query->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('number', $orderNumber));
                }
            })
            ->first();

        if (! $payment) {
            Log::info('stripe.webhook_unmatched', ['type' => $event['type'], 'event' => $event['id']]);

            return response('ok', 200);
        }

        DB::transaction(function () use ($payment, $event, $object) {
            $payment = Payment::query()->lockForUpdate()->find($payment->id);
            $order = $payment->order()->lockForUpdate()->first();
            if (! $payment || ! $order) {
                return;
            }

            if ($payment->provider_event_id === $event['id'] || ($payment->status === 'paid' && in_array($event['type'], ['checkout.session.completed', 'payment_intent.succeeded'], true))) {
                return;
            }

            if (in_array($event['type'], ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
                $paid = ($object['payment_status'] ?? $object['status'] ?? '') === 'paid'
                    || ($object['status'] ?? '') === 'succeeded'
                    || $event['type'] === 'checkout.session.completed';

                if ($paid) {
                    $this->fulfillment->markPaid($order, $payment, [
                        'provider_event_id' => $event['id'],
                        'method' => 'card',
                        'amount_minor' => isset($object['amount_total']) ? (int) $object['amount_total'] : Money::toMinor($order->total),
                        'payload' => ['event' => $event['type']],
                    ]);
                }

                return;
            }

            if (in_array($event['type'], ['checkout.session.expired', 'payment_intent.payment_failed', 'checkout.session.async_payment_failed'], true)) {
                $this->fulfillment->markFailed($order, $payment, 'Payment failed.', $event['type'] === 'checkout.session.expired' ? 'canceled' : 'failed');
            }
        });

        return response('ok', 200);
    }
}
