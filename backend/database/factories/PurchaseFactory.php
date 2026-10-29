<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $tax = round($subtotal * 0.15, 2);
        $total = round($subtotal + $tax, 2);

        return [
            'supplier_id' => Supplier::factory(),
            'reference_number' => fake()->unique()->bothify('PO-#####'),
            'status' => fake()->randomElement(['draft', 'ordered', 'received', 'cancelled']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid', 'overpaid']),
            'expected_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'received_at' => null,
            'notes' => fake()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
