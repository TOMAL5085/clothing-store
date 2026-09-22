<?php

namespace App\Services\Messaging;

use InvalidArgumentException;

class MessageGatewayResolver
{
    /**
     * Resolve the configured gateway for an outbound channel. Only known
     * driver names resolve — anything else throws instead of silently
     * dropping messages or, worse, sending through a wrong provider.
     *
     * @throws InvalidArgumentException when the channel or driver is unknown.
     */
    public function for(string $channel): MessageGateway
    {
        if (! in_array($channel, ['sms', 'whatsapp'], true)) {
            throw new InvalidArgumentException("Unknown messaging channel [{$channel}].");
        }

        $driver = (string) config("messaging.{$channel}_driver", 'mock');
        $class = config("messaging.drivers.{$driver}.{$channel}_class");

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException("Messaging driver [{$driver}] for channel [{$channel}] is not configured.");
        }

        $gateway = app($class);

        if (! $gateway instanceof MessageGateway) {
            throw new InvalidArgumentException("Messaging driver [{$driver}] does not implement MessageGateway.");
        }

        return $gateway;
    }

    /**
     * Whether the channel has a known, resolvable driver. Used before
     * creating delivery rows so misconfiguration never fabricates rows.
     */
    public function configured(string $channel): bool
    {
        try {
            $this->for($channel);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
