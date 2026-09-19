<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderFulfillmentService;
use App\Services\Payments\SslCommerzGateway;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SslCommerzController extends Controller
{
    public function __construct(
        private readonly SslCommerzGateway $gateway,
        private readonly OrderFulfillmentService $fulfillment,
    ) {}

    public function ipn(Request $request): Response
    {
        $this->handle($request->all());

        return response('SUCCESS', 200);
    }

    public function success(Request $request): RedirectResponse
    {
        $result = $this->handle($request->all());
        $order = $result['order'] ?? null;
        $frontend = rtrim((string) config('payments.frontend_url'), '/');

        if ($order && $order->payment_status === 'paid') {
            return redirect()->away($frontend.'/order/success/'.$order->number.'?checkout_token='.$order->checkout_token);
        }

        return redirect()->away($frontend.'/order/failed?reason='.urlencode('Payment could not be verified.'));
    }

    public function fail(Request $request): RedirectResponse
    {
        $this->handle($request->all(), 'failed');
        $frontend = rtrim((string) config('payments.frontend_url'), '/');

        return redirect()->away($frontend.'/order/failed?reason='.urlencode('Payment failed.'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->handle($request->all(), 'canceled');
        $frontend = rtrim((string) config('payments.frontend_url'), '/');

        return redirect()->away($frontend.'/order/failed?reason='.urlencode('Payment was canceled.'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{order:?\App\Models\Order}
     */
    private function handle(array $payload, ?string $forcedStatus = null): array
    {
        $validated = $this->gateway->validateIpn($payload);
        $tranId = $validated['tran_id'] ?? ($payload['tran_id'] ?? null);
        if (! is_string($tranId) || $tranId === '') {
            Log::warning('sslcommerz.callback_missing_tran');

            return ['order' => null];
        }

        return DB::transaction(function () use ($validated, $tranId, $forcedStatus) {
            $payment = Payment::query()->where('provider', 'sslcommerz')->where('reference', $tranId)->lockForUpdate()->first();
            if (! $payment) {
                return ['order' => null];
            }

            $order = $payment->order()->lockForUpdate()->first();
            if (! $order) {
                return ['order' => null];
            }

            if ($payment->status === 'paid') {
                return ['order' => $order];
            }

            $status = $forcedStatus ?? match ($validated['status']) {
                'VALID' => 'paid',
                'CANCELLED' => 'canceled',
                default => 'failed',
            };

            if ($status === 'paid') {
                if ($validated['amount'] !== null && Money::toMinor($validated['amount']) !== Money::toMinor($order->total)) {
                    Log::warning('sslcommerz.amount_mismatch', ['order' => $order->number]);
                    $this->fulfillment->markFailed($order, $payment, 'Payment validation failed.');

                    return ['order' => $order->refresh()];
                }

                $eventId = $validated['val_id'] ?? $tranId;
                $this->fulfillment->markPaid($order, $payment, [
                    'provider_event_id' => $eventId,
                    'method' => 'sslcommerz',
                    'payload' => ['status' => 'VALID'],
                ]);
            } else {
                $this->fulfillment->markFailed($order, $payment, $status === 'canceled' ? 'Payment was canceled.' : 'Payment failed.', $status);
            }

            return ['order' => $order->refresh()];
        });
    }
}
