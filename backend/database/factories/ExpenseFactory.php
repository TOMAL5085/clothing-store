<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('EXP-#####'),
            'account_id' => Account::factory(),
            'category' => fake()->randomElement(['office', 'marketing', 'shipping', 'utilities', 'travel', 'other']),
            'vendor' => fake()->company(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'USD',
            'expense_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => fake()->randomElement(Expense::STATUSES),
            'description' => fake()->sentence(),
            'receipt_path' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
