<?php

$frontendUrls = array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173')))));

return [
    'driver' => env('PAYMENT_DRIVER', 'demo'),
    'store_currency' => env('PAYMENT_STORE_CURRENCY', 'USD'),
    'frontend_url' => env('PAYMENT_FRONTEND_URL', $frontendUrls[0] ?? 'http://localhost:5173'),

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', env('PAYMENT_STORE_CURRENCY', 'USD')),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox' => filter_var(env('SSLCOMMERZ_SANDBOX', true), FILTER_VALIDATE_BOOL),
        'currency' => env('SSLCOMMERZ_CURRENCY', env('PAYMENT_STORE_CURRENCY', 'USD')),
    ],
];
