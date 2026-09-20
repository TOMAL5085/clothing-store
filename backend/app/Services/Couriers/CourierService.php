<?php

namespace App\Services\Couriers;

use InvalidArgumentException;

class CourierService
{
    public function __construct(
        private readonly CourierGateway $gateway
    ) {}

    public function gateway(): CourierGateway
    {
        return $this->gateway;
    }

    public function gatewayFor(string $driver): CourierGateway
    {
        $provider = config("couriers.providers.{$driver}");

        if (! is_array($provider) || blank($provider['class'] ?? null)) {
            throw new InvalidArgumentException("Courier driver [{$driver}] is not configured.");
        }

        return app($provider['class']);
    }
}