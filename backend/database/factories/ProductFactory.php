<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->slug(),
            'category_id' => Category::factory(),
            'slug' => fake()->unique()->slug(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 40, 500),
            'compare_at_price' => null,
            'sku' => 'JAAJ-'.fake()->unique()->bothify('????-####'),
            'brand' => 'JAAJ',
            'rating' => fake()->randomFloat(1, 4, 5),
            'reviews_count' => fake()->numberBetween(0, 500),
            'badge' => null,
            'is_new' => false,
            'is_bestseller' => false,
            'is_featured' => false,
            'is_active' => true,
            'in_stock' => true,
            'stock_quantity' => 20,
            'details' => ['Machine washable'],
        ];
    }
}
