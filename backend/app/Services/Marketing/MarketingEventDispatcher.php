<?php

namespace App\Services\Marketing;

use App\Jobs\DeliverMarketingEvent;
use App\Models\MarketingEvent;
use App\Models\MarketingEventDelivery;
use Illuminate\Support\Facades\Log;

class MarketingEventDispatcher
{
    public function __construct(
        private readonly MarketingProviderResolver $providers,
    ) {}

    /**
     * Fan out one persisted event to every enabled provider. Never throws:
     * measurement must not break checkout, webhooks, or any mutation.
     * Provider delivery additionally requires marketing consent; the
     * ledger row itself is already stored by the caller.
     */
    public function fanout(MarketingEvent $event): void
    {
        try {
            $this->dispatch($event);
        } catch (\Throwable $exception) {
            Log::warning('marketing.fanout_failed', [
                'event_id' => $event->event_id,
                'event_name' => $event->event_name,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatch(MarketingEvent $event): void
    {
        if (! $this->marketingConsented($event)) {
            return;
        }

        foreach ($this->providers->all() as $provider) {
            if (! $provider->supports($event)) {
                continue;
            }

            $delivery = MarketingEventDelivery::query()->firstOrCreate(
                ['marketing_event_id' => $event->id, 'provider' => $provider->name()],
                ['status' => MarketingEventDelivery::STATUS_QUEUED]
            );

            if ($delivery->wasRecentlyCreated) {
                DeliverMarketingEvent::dispatch($delivery->id);
            }
        }
    }

    private function marketingConsented(MarketingEvent $event): bool
    {
        if ($event->user_id) {
            $user = $event->user;

            if ($user && is_array($user->marketing_consent)) {
                return (bool) ($user->marketing_consent['marketing'] ?? false);
            }
        }

        return match ($event->consent_state) {
            'granted', 'marketing' => true,
            default => false,
        };
    }
}
