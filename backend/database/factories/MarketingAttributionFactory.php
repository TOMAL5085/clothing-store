<?php

namespace Database\Factories;

use App\Models\MarketingAttribution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingAttribution>
 */
class MarketingAttributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anonymous_id' => (string) Str::uuid(),
            'user_id' => null,
            'session_id' => (string) Str::uuid(),
            'source' => 'newsletter',
            'medium' => 'email',
            'campaign' => 'welcome',
            'term' => null,
            'content' => null,
            'click_ids' => [],
            'landing_url' => 'http://localhost:5173/',
            'referrer' => null,
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ];
    }
}
