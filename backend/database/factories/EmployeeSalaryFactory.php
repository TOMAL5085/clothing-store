<?php

namespace Database\Factories;

use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $base = fake()->randomFloat(2, 2000, 8000);
        $allowances = fake()->randomFloat(2, 0, 1000);
        $deductions = fake()->randomFloat(2, 0, 500);
        $net = round($base + $allowances - $deductions, 2);

        return [
            'user_id' => User::factory(),
            'reference_number' => fake()->unique()->bothify('SAL-#####'),
            'month' => fake()->dateTimeBetween('-6 months', '+1 month')->format('Y-m'),
            'base_salary' => $base,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $net,
            'status' => fake()->randomElement(EmployeeSalary::STATUSES),
            'payment_date' => null,
            'payment_method' => fake()->randomElement(['bank_transfer', 'cash', 'mobile_money']),
            'notes' => fake()->sentence(),
            'approved_by' => null,
            'approved_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
