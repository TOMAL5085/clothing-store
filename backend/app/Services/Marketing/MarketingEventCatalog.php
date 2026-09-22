<?php

namespace App\Services\Marketing;

class MarketingEventCatalog
{
    /**
     * Central event catalog. No controller, component, or provider may use
     * an event name outside this list.
     *
     * Flags per event:
     * - source:      which side is authoritative ('client'|'server')
     * - conversion:  whether the event counts as a conversion
     * - anonymous:   whether unauthenticated visitors may emit it
     * - value:       whether a monetary value is meaningful
     * - metadata:    the only permitted metadata keys for the event
     *
     * @var array<string, array{source:string, conversion:bool, anonymous:bool, value:bool, metadata:list<string>}>
     */
    public const EVENTS = [
        'page_view' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['path'],
        ],
        'product_view' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['product_external_id', 'slug', 'category', 'currency'],
        ],
        'search' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['query', 'result_count'],
        ],
        'add_to_cart' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['product_external_id', 'slug', 'quantity'],
        ],
        'remove_from_cart' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['product_external_id', 'slug', 'quantity'],
        ],
        'view_cart' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => false,
            'metadata' => ['item_count'],
        ],
        'begin_checkout' => [
            'source' => 'client', 'conversion' => false, 'anonymous' => true, 'value' => true,
            'metadata' => ['item_count', 'currency'],
        ],
        'purchase' => [
            'source' => 'server', 'conversion' => true, 'anonymous' => true, 'value' => true,
            'metadata' => ['lines'],
        ],
        'payment_failed' => [
            'source' => 'server', 'conversion' => false, 'anonymous' => true, 'value' => true,
            'metadata' => [],
        ],
        'order_cancelled' => [
            'source' => 'server', 'conversion' => false, 'anonymous' => true, 'value' => true,
            'metadata' => [],
        ],
        'refund_completed' => [
            'source' => 'server', 'conversion' => false, 'anonymous' => true, 'value' => true,
            'metadata' => [],
        ],
    ];

    public static function names(): array
    {
        return array_keys(self::EVENTS);
    }

    public static function isServerOnly(string $event): bool
    {
        return (self::EVENTS[$event]['source'] ?? null) === 'server';
    }

    /** @return list<string> */
    public static function metadataKeys(string $event): array
    {
        return self::EVENTS[$event]['metadata'] ?? [];
    }
}
