<?php

use App\Services\Marketing\MockMarketingProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | First-party marketing measurement
    |--------------------------------------------------------------------------
    |
    | Provider-neutral conversion tracking foundation. Events are recorded in
    | local tables first; external delivery happens asynchronously through
    | configured providers. No real marketing vendor is selected — the only
    | bundled provider is the deterministic "mock" sink for development and
    | tests, plus "null" which disables outbound delivery entirely.
    |
    */

    'events_enabled' => env('MARKETING_EVENTS_ENABLED', true),

    'providers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MARKETING_PROVIDERS', 'mock'))
    ))),

    'drivers' => [
        'mock' => [
            'driver' => 'mock',
            'class' => MockMarketingProvider::class,
        ],
    ],

    'retention_days' => (int) env('MARKETING_EVENT_RETENTION_DAYS', 90),

];
