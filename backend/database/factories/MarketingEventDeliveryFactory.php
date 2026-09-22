<?php

namespace Database\Factories;

use App\Models\MarketingEvent;
use App\Models\MarketingEventDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingEventDelivery>
 */
class MarketingEventDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marketing_event_id' => MarketingEvent::factory(),
            'provider' => 'mock',
            'status' => MarketingEventDelivery::STATUS_QUEUED,
            'attempts' => 0,
            'provider_event_id' => null,
            'last_attempted_at' => null,
            'sent_at' => null,
            'failed_at' => null,
            'error_code' => null,
        ];
    }
}
