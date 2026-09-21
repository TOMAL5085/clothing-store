<?php

return [
    'cancellation' => [
        'allowed_order_statuses' => ['pending', 'confirmed', 'processing'],
        'blocked_shipment_statuses' => ['shipped', 'in_transit', 'out_for_delivery', 'delivered'],
    ],

    'returns' => [
        'allowed_order_statuses' => ['delivered'],
    ],
];
