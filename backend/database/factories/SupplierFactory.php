<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Supplier;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'contact_person' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_id' => fake()->regexify('[0-9]{9}'),
            'address' => fake()->streetAddress(),
            'city_id' => City::factory(),
            'zone_id' => Zone::factory(),
            'status' => 'active',
            'sort_order' => fake()->numberBetween(1, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
