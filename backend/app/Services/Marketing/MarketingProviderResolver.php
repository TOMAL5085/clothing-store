<?php

namespace App\Services\Marketing;

use InvalidArgumentException;

class MarketingProviderResolver
{
    /**
     * Resolve the configured providers for outbound delivery. Unknown names
     * throw instead of silently dropping events or sending through a wrong
     * provider. The "null" driver resolves to no providers (disabled).
     *
     * @return list<MarketingProvider>
     *
     * @throws InvalidArgumentException when a configured provider is unknown.
     */
    public function all(): array
    {
        $names = config('marketing.providers', ['mock']);

        if (! is_array($names)) {
            $names = [$names];
        }

        $providers = [];

        foreach (array_unique(array_map('strval', $names)) as $name) {
            if ($name === '' || $name === 'null') {
                continue;
            }

            $class = config("marketing.drivers.{$name}.class");

            if (! is_string($class) || ! class_exists($class)) {
                throw new InvalidArgumentException("Marketing provider [{$name}] is not configured.");
            }

            $provider = app($class);

            if (! $provider instanceof MarketingProvider) {
                throw new InvalidArgumentException("Marketing provider [{$name}] does not implement MarketingProvider.");
            }

            $providers[] = $provider;
        }

        return $providers;
    }

    public function configured(): bool
    {
        try {
            return $this->all() !== [];
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
