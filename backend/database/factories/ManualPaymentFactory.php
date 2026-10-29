<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ManualPayment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualPayment>
 */
class ManualPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('MAN-#####'),
            'order_id' => Order::factory(),
            'account_id' => Account::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'payment_method' => fake()->randomElement(ManualPayment::PAYMENT_METHODS),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'USD',
            'payment_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => fake()->randomElement(ManualPayment::STATUSES),
            'transaction_id' => fake()->optional()->bothify('TXN-#####'),
            'notes' => fake()->sentence(),
            'receipt_path' => null,
            'verified_by' => null,
            'verified_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
