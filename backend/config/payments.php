<?php

$frontendUrls = array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URLS', 'http://localhost:5173')))));
$testing = env('APP_ENV') === 'testing';

return [
    'driver' => env('PAYMENT_DRIVER', 'demo'),
    'store_currency' => env('PAYMENT_STORE_CURRENCY', 'USD'),
    'frontend_url' => env('PAYMENT_FRONTEND_URL', $frontendUrls[0] ?? 'http://localhost:5173'),

    'stripe' => [
        'key' => env('STRIPE_KEY') ?: ($testing ? 'pk_test_placeholder' : null),
        'secret' => env('STRIPE_SECRET') ?: ($testing ? 'sk_test_placeholder' : null),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET') ?: ($testing ? 'whsec_test_placeholder' : null),
        'currency' => env('STRIPE_CURRENCY', env('PAYMENT_STORE_CURRENCY', 'USD')),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID') ?: ($testing ? 'testbox' : null),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD') ?: ($testing ? 'test_password' : null),
        'sandbox' => filter_var(env('SSLCOMMERZ_SANDBOX', true), FILTER_VALIDATE_BOOL),
        'currency' => env('SSLCOMMERZ_CURRENCY', env('PAYMENT_STORE_CURRENCY', 'USD')),
    ],
];
