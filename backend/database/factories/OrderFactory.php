<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'JAAJ-'.fake()->unique()->numberBetween(100000, 999999),
            'shipping_address_id' => Address::create([
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => fake()->safeEmail(),
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
                'postal_code' => fake()->postcode(),
                'country' => 'Denmark',
            ])->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'delivery_method' => 'standard',
            'subtotal' => 100,
            'discount' => 0,
            'shipping' => 9.95,
            'tax' => 0,
            'total' => 109.95,
        ];
    }
}
