<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => 'pending',
            'verified_purchase' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'verified_purchase' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'rejected']);
    }
}
