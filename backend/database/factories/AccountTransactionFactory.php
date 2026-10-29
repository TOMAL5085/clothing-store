<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountTransaction>
 */
class AccountTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'reference_type' => 'manual',
            'reference_id' => 1,
            'direction' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'description' => fake()->sentence(),
            'transaction_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'created_by' => null,
        ];
    }
}
