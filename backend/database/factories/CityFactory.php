<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => Zone::factory(),
            'name' => fake()->city(),
            'code' => fake()->unique()->regexify('[A-Z]{3}'),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
