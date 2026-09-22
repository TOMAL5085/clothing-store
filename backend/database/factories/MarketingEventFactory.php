<?php

namespace Database\Factories;

use App\Models\MarketingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingEvent>
 */
class MarketingEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'event_name' => 'product_view',
            'event_source' => 'client',
            'user_id' => null,
            'anonymous_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
            'order_id' => null,
            'order_number' => null,
            'product_external_id' => null,
            'occurred_at' => now(),
            'received_at' => now(),
            'currency' => null,
            'value' => null,
            'attribution_id' => null,
            'metadata' => [],
            'consent_state' => 'granted',
        ];
    }
}
