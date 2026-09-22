<?php

use App\Services\Messaging\MockSmsGateway;
use App\Services\Messaging\MockWhatsappGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Outbound messaging drivers
    |--------------------------------------------------------------------------
    |
    | Provider-neutral SMS / WhatsApp delivery. The "mock" driver is
    | deterministic and network-free for local development and tests.
    | When a real provider is selected, add its driver name here and bind
    | its gateway in MessageGatewayResolver — no notification code changes.
    |
    */

    'sms_driver' => env('SMS_DRIVER', 'mock'),

    'whatsapp_driver' => env('WHATSAPP_DRIVER', 'mock'),

    'drivers' => [
        'mock' => [
            'driver' => 'mock',
            'sms_class' => MockSmsGateway::class,
            'whatsapp_class' => MockWhatsappGateway::class,
        ],
    ],

];
