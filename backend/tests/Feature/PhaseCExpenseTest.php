<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_access_expenses(): void
    {
        $this->getJson('/api/v1/admin/expenses')->assertUnauthorized();
    }

    public function test_customer_cannot_access_expenses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/expenses')
            ->assertForbidden();
    }

    public function test_admin_can_create_expense(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create(['type' => 'expense']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/expenses', [
                'reference_number' => 'EXP-00001',
                'account_id' => $account->id,
                'category' => 'marketing',
                'vendor' => 'Google Ads',
                'amount' => 500.00,
                'currency' => 'USD',
                'expense_date' => '2026-10-29',
                'status' => 'pending',
                'description' => 'Online advertising',
            ])->assertCreated()
            ->assertJsonPath('data.reference_number', 'EXP-00001')
            ->assertJsonPath('data.amount', 500);

        $this->assertDatabaseHas('expenses', ['reference_number' => 'EXP-00001']);
    }

    public function test_admin_expense_rejects_duplicate_reference(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create();
        Expense::factory()->create(['reference_number' => 'EXP-DUP', 'account_id' => $account->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/expenses', [
                'reference_number' => 'EXP-DUP',
                'account_id' => $account->id,
                'category' => 'marketing',
                'amount' => 100.00,
            ])->assertUnprocessable();
    }

    public function test_admin_can_update_expense_status(): void
    {
        $admin = $this->admin();
        $expense = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/expenses/{$expense->reference_number}", [
                'status' => 'approved',
            ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('expenses', ['reference_number' => $expense->reference_number, 'status' => 'approved']);
    }

    public function test_admin_can_delete_expense(): void
    {
        $admin = $this->admin();
        $expense = Expense::factory()->create(['reference_number' => 'EXP-DELETE']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/expenses/{$expense->reference_number}")
            ->assertOk();

        $this->assertDatabaseMissing('expenses', ['reference_number' => 'EXP-DELETE']);
    }

    public function test_admin_expense_listing_supports_filters(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create();

        Expense::factory()->create(['reference_number' => 'EXP-A', 'account_id' => $account->id, 'category' => 'marketing', 'status' => 'pending']);
        Expense::factory()->create(['reference_number' => 'EXP-B', 'account_id' => $account->id, 'category' => 'shipping', 'status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/expenses?category=marketing')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('marketing', $response->json('data.0.category'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/expenses?status=approved')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('EXP-B', $response->json('data.0.reference_number'));
    }
}
