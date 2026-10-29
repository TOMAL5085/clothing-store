<?php

namespace Tests\Feature;

use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCEmployeeSalaryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_access_salaries(): void
    {
        $this->getJson('/api/v1/admin/salaries')->assertUnauthorized();
    }

    public function test_admin_can_create_salary(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/salaries', [
                'user_id' => $user->id,
                'reference_number' => 'SAL-00001',
                'month' => '2026-10',
                'base_salary' => 3000.00,
                'allowances' => 500.00,
                'deductions' => 200.00,
                'net_salary' => 3300.00,
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
                'notes' => 'October salary',
            ])->assertCreated()
            ->assertJsonPath('data.reference_number', 'SAL-00001')
            ->assertJsonPath('data.net_salary', 3300);

        $this->assertDatabaseHas('employee_salaries', ['reference_number' => 'SAL-00001']);
    }

    public function test_admin_salary_rejects_duplicate_month_for_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        EmployeeSalary::factory()->create(['user_id' => $user->id, 'month' => '2026-10', 'reference_number' => 'SAL-DUP']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/salaries', [
                'user_id' => $user->id,
                'reference_number' => 'SAL-DUP-2',
                'month' => '2026-10',
                'base_salary' => 3000.00,
                'net_salary' => 3000.00,
            ])->assertUnprocessable();
    }

    public function test_admin_can_update_salary_status(): void
    {
        $admin = $this->admin();
        $salary = EmployeeSalary::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/salaries/{$salary->reference_number}", [
                'status' => 'paid',
                'payment_date' => '2026-10-30',
            ])->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('employee_salaries', ['reference_number' => $salary->reference_number, 'status' => 'paid']);
    }

    public function test_admin_can_delete_salary(): void
    {
        $admin = $this->admin();
        $salary = EmployeeSalary::factory()->create(['reference_number' => 'SAL-DELETE']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/salaries/{$salary->reference_number}")
            ->assertOk();

        $this->assertDatabaseMissing('employee_salaries', ['reference_number' => 'SAL-DELETE']);
    }

    public function test_admin_salary_listing_supports_filters(): void
    {
        $admin = $this->admin();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        EmployeeSalary::factory()->create(['reference_number' => 'SAL-A', 'user_id' => $userA->id, 'month' => '2026-10', 'status' => 'pending']);
        EmployeeSalary::factory()->create(['reference_number' => 'SAL-B', 'user_id' => $userB->id, 'month' => '2026-10', 'status' => 'paid']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/salaries?month=2026-10')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/salaries?status=paid')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('SAL-B', $response->json('data.0.reference_number'));
    }
}
