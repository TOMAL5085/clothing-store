<?php

namespace App\Services\Marketing;

use App\Models\MarketingEvent;

class MockMarketingProvider implements MarketingProvider
{
    /** @var list<array{event_id:string, event_name:string, idempotency_key:?string}> */
    public static array $outbox = [];

    public static int $counter = 0;

    /** @var list<string> Event IDs that should fail transiently. */
    public static array $failOnce = [];

    /** @var list<string> Event IDs that should fail permanently. */
    public static array $failAlways = [];

    public function name(): string
    {
        return 'mock';
    }

    public function supports(MarketingEvent $event): bool
    {
        return true;
    }

    public function send(MarketingEvent $event, array $options = []): array
    {
        if (in_array($event->event_id, self::$failAlways, true)) {
            throw new MarketingProviderException('Mock provider permanently rejected the event.', 'mock_rejected', false);
        }

        if (in_array($event->event_id, self::$failOnce, true)) {
            self::$failOnce = array_values(array_diff(self::$failOnce, [$event->event_id]));

            throw new MarketingProviderException('Mock provider transient failure.', 'mock_transient', true);
        }

        self::$counter++;
        self::$outbox[] = [
            'event_id' => $event->event_id,
            'event_name' => $event->event_name,
            'idempotency_key' => $options['event_id'] ?? null,
        ];

        return [
            'provider_event_id' => 'mock-evt-'.self::$counter,
            'status' => 'sent',
        ];
    }

    /** @return list<array{event_id:string, event_name:string, idempotency_key:?string}> */
    public static function sent(): array
    {
        return self::$outbox;
    }

    public static function reset(): void
    {
        self::$outbox = [];
        self::$counter = 0;
        self::$failOnce = [];
        self::$failAlways = [];
    }
}
