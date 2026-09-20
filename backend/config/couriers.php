<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Courier Driver
    |--------------------------------------------------------------------------
    |
    | This option controls which courier provider will be used by the application.
    | The "mock" driver is used for local development and testing.
    | When a real courier provider is selected, add its driver here.
    |
    */
    'default' => env('COURIER_DRIVER', 'mock'),

    'providers' => [
        'mock' => [
            'driver' => 'mock',
            'class' => \App\Services\Couriers\MockCourierGateway::class,
        ],
    ],
];