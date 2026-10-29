<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $unitCost = fake()->randomFloat(2, 5, 200);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'received_quantity' => 0,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
        ];
    }
}
