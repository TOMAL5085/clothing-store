<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('????10'),
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
            'starts_at' => null,
            'expires_at' => null,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'per_customer_limit' => null,
        ];
    }
}
