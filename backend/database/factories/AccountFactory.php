<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Account::TYPES);

        return [
            'code' => fake()->unique()->bothify('ACC-####'),
            'name' => fake()->company(),
            'type' => $type,
            'subtype' => fake()->randomElement(['bank', 'cash', 'receivable', 'payable', 'operating']),
            'opening_balance' => fake()->randomFloat(2, 0, 10000),
            'current_balance' => fake()->randomFloat(2, 0, 10000),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
