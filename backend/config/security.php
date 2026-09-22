<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate limits
    |--------------------------------------------------------------------------
    |
    | Thresholds for the named API rate limiters registered in
    | AppServiceProvider. Keys segment authenticated users by ID and guests
    | by IP, so ordinary shared-network browsing is never blocked by a
    | single abusive client. Webhook/callback routes are intentionally
    | absent: providers retry delivery and must never be throttled.
    |
    */
    'rate_limits' => [
        'login' => ['per_minute' => 5, 'per_hour' => 30],
        'register' => ['per_minute' => 3, 'per_hour' => 10],
        'password_reset' => ['per_minute' => 3, 'per_hour' => 10],
        'otp' => ['per_minute' => 10],
        'order_lookup' => ['per_minute' => 20],
        'checkout_quote' => ['per_minute' => 30],
        'checkout' => ['per_minute' => 6, 'per_hour' => 60],
        'promo' => ['per_minute' => 10, 'per_hour' => 100],
        'reviews' => ['per_minute' => 10, 'per_hour' => 100],
        'storefront_mutations' => ['per_minute' => 60],
        'resolution_requests' => ['per_minute' => 10, 'per_hour' => 60],
        'admin_mutations' => ['per_minute' => 120],
        'marketing_events' => ['per_minute' => 60, 'per_hour' => 600],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit log retention
    |--------------------------------------------------------------------------
    |
    | Number of days audit log rows are kept. Rows older than this are
    | removed by `php artisan audit:prune`.
    |
    */
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
