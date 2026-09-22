<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Services\Messaging\MessageGatewayException;
use App\Services\Messaging\MessageGatewayResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class SendOutboundMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Bounded retries: transient provider failures are retried with
     * backoff, then the job lands in failed_jobs and the delivery row is
     * marked failed. Permanent rejection (e.g. invalid recipient) still
     * throws so the attempt is recorded in the standard way.
     */
    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function __construct(
        public int $deliveryId,
        public string $body,
    ) {}

    public function handle(MessageGatewayResolver $resolver): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        // Idempotency: a retry after a successful send must not resend.
        if ($delivery->isTerminal()) {
            return;
        }

        try {
            $gateway = $resolver->for($delivery->channel);
        } catch (\InvalidArgumentException $exception) {
            // Driver misconfiguration cannot heal by retrying the same job.
            $delivery->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => 'driver_not_configured',
            ]);
            Log::warning('messaging.driver_not_configured', $this->context($delivery));

            return;
        }

        $delivery->update([
            'attempts' => $delivery->attempts + 1,
            'last_attempted_at' => now(),
            'provider' => $gateway->name(),
        ]);

        try {
            $result = $gateway->send($delivery->recipient, $this->body, [
                'idempotency_key' => $delivery->message_key,
            ]);
        } catch (MessageGatewayException $exception) {
            $delivery->update(['error_code' => $exception->errorCode()]);
            Log::warning('messaging.send_failed', $this->context($delivery, $exception->errorCode()));

            throw $exception;
        } catch (\Throwable $exception) {
            $delivery->update(['error_code' => 'gateway_error']);
            Log::warning('messaging.send_failed', $this->context($delivery, 'gateway_error'));

            throw $exception;
        }

        $delivery->update([
            'status' => NotificationDelivery::STATUS_SENT,
            'provider_message_id' => $result['provider_message_id'] ?? null,
            'error_code' => null,
        ]);
        Log::info('messaging.sent', $this->context($delivery));
    }

    /**
     * Final failure hook: the worker calls this once retries are
     * exhausted, so the delivery row reflects reality even though the
     * exception path above already recorded the error code.
     */
    public function failed(?\Throwable $exception = null): void
    {
        $errorCode = $exception instanceof MessageGatewayException
            ? $exception->errorCode()
            : 'gateway_error';

        NotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->whereNotIn('status', [NotificationDelivery::STATUS_SENT, NotificationDelivery::STATUS_DELIVERED])
            ->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => $errorCode,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(NotificationDelivery $delivery, ?string $errorCode = null): array
    {
        return array_filter([
            'delivery_id' => $delivery->id,
            'channel' => $delivery->channel,
            'provider' => $delivery->provider,
            'order_number' => $delivery->order_number,
            'recipient' => self::maskRecipient($delivery->recipient),
            'attempts' => $delivery->attempts,
            'error_code' => $errorCode ?? $delivery->error_code,
            'request_id' => Context::get('request_id'),
        ], fn ($value) => $value !== null);
    }

    public static function maskRecipient(string $recipient): string
    {
        return '***'.substr($recipient, -4);
    }
}
