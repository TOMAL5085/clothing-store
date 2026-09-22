<?php

namespace App\Services\Marketing;

use App\Models\MarketingEvent;

interface MarketingProvider
{
    /**
     * Provider driver name, e.g. "mock". Used for delivery records and
     * logs — never a secret.
     */
    public function name(): string;

    /**
     * Whether this provider accepts the given event. Providers filter here
     * (e.g. conversions only) instead of the core app knowing vendor rules.
     */
    public function supports(MarketingEvent $event): bool;

    /**
     * Deliver one normalized event. Implementations perform the single
     * provider call here and nowhere else, so retries stay centralized.
     *
     * @param  array{event_id:string}  $options  Stable idempotency key included.
     * @return array{provider_event_id:string, status:string}
     *
     * @throws MarketingProviderException on transport or provider rejection.
     */
    public function send(MarketingEvent $event, array $options = []): array;
}
