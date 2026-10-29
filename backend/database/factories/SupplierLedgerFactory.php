<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierLedger>
 */
class SupplierLedgerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debit = fake()->randomFloat(2, 0, 1000);
        $credit = fake()->randomFloat(2, 0, 1000);

        return [
            'supplier_id' => Supplier::factory(),
            'reference_type' => 'purchase',
            'reference_id' => 1,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => round($debit - $credit, 2),
            'description' => fake()->sentence(),
        ];
    }
}
