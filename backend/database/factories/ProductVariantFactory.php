<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size_id' => Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3])->id,
            'color_id' => Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17'])->id,
            'sku' => 'JAAJ-'.fake()->unique()->bothify('VAR-####'),
            'price' => null,
            'stock_quantity' => 10,
            'is_active' => true,
        ];
    }
}
