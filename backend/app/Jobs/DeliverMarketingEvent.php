<?php

namespace App\Jobs;

use App\Models\MarketingEventDelivery;
use App\Services\Marketing\MarketingProvider;
use App\Services\Marketing\MarketingProviderException;
use App\Services\Marketing\MarketingProviderResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class DeliverMarketingEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Bounded retries: transient provider failures are retried with
     * backoff, then the job lands in failed_jobs and the delivery row is
     * marked failed. Permanent rejections skip retries via failed().
     */
    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function __construct(
        public int $deliveryId,
    ) {}

    public function handle(MarketingProviderResolver $resolver): void
    {
        $delivery = MarketingEventDelivery::query()->with('event')->find($this->deliveryId);

        if (! $delivery || ! $delivery->event) {
            return;
        }

        // Idempotency: a retry after a successful send must not resend.
        // The (event, provider) unique key plus this guard make redelivery
        // safe across worker retries and process crashes.
        if ($delivery->isTerminal()) {
            return;
        }

        try {
            $provider = $this->resolveProvider($resolver, $delivery);
        } catch (\InvalidArgumentException $exception) {
            $delivery->update([
                'status' => MarketingEventDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => 'provider_not_configured',
            ]);
            Log::warning('marketing.provider_not_configured', $this->context($delivery));

            return;
        }

        $delivery->update([
            'attempts' => $delivery->attempts + 1,
            'last_attempted_at' => now(),
        ]);

        try {
            $result = $provider->send($delivery->event, [
                'event_id' => $delivery->event->event_id,
            ]);
        } catch (MarketingProviderException $exception) {
            $delivery->update(['error_code' => $exception->errorCode()]);
            Log::warning('marketing.delivery_failed', $this->context($delivery, $exception->errorCode()));

            if (! $exception->isRetryable()) {
                $this->fail($exception);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            $delivery->update(['error_code' => 'provider_error']);
            Log::warning('marketing.delivery_failed', $this->context($delivery, 'provider_error'));

            throw $exception;
        }

        $delivery->update([
            'status' => MarketingEventDelivery::STATUS_SENT,
            'provider_event_id' => $result['provider_event_id'] ?? null,
            'sent_at' => now(),
            'error_code' => null,
        ]);
        Log::info('marketing.delivered', $this->context($delivery));
    }

    /**
     * Final failure hook: the worker calls this once retries are
     * exhausted, so the delivery row reflects reality.
     */
    public function failed(?\Throwable $exception = null): void
    {
        $errorCode = $exception instanceof MarketingProviderException
            ? $exception->errorCode()
            : 'provider_error';

        MarketingEventDelivery::query()
            ->whereKey($this->deliveryId)
            ->whereNotIn('status', [MarketingEventDelivery::STATUS_SENT])
            ->update([
                'status' => MarketingEventDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => $errorCode,
            ]);
    }

    private function resolveProvider(MarketingProviderResolver $resolver, MarketingEventDelivery $delivery): MarketingProvider
    {
        foreach ($resolver->all() as $provider) {
            if ($provider->name() === $delivery->provider) {
                return $provider;
            }
        }

        throw new \InvalidArgumentException("Marketing provider [{$delivery->provider}] is not configured.");
    }

    /**
     * @return array<string, mixed>
     */
    private function context(MarketingEventDelivery $delivery, ?string $errorCode = null): array
    {
        return array_filter([
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->event?->event_id,
            'event_name' => $delivery->event?->event_name,
            'provider' => $delivery->provider,
            'attempts' => $delivery->attempts,
            'error_code' => $errorCode ?? $delivery->error_code,
            'request_id' => Context::get('request_id'),
        ], fn ($value) => $value !== null);
    }
}
