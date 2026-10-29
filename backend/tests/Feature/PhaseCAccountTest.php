<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_account_listing_returns_active_accounts(): void
    {
        Account::factory()->create(['code' => 'BANK-01', 'name' => 'Bank Account', 'type' => 'asset', 'is_active' => true]);
        Account::factory()->create(['code' => 'CASH-01', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        Account::factory()->create(['code' => 'INACTIVE', 'name' => 'Inactive', 'type' => 'asset', 'is_active' => false]);

        $response = $this->getJson('/api/v1/accounts')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $codes = array_column($response->json('data'), 'code');
        $this->assertContains('BANK-01', $codes);
        $this->assertContains('CASH-01', $codes);
        $this->assertNotContains('INACTIVE', $codes);
    }

    public function test_public_account_show_404_for_inactive(): void
    {
        $account = Account::factory()->create(['code' => 'HIDDEN', 'name' => 'Hidden', 'is_active' => false]);

        $this->getJson("/api/v1/accounts/{$account->code}")->assertNotFound();
    }

    public function test_guest_cannot_create_account(): void
    {
        $this->postJson('/api/v1/admin/accounts', [
            'code' => 'TEST-01',
            'name' => 'Test Account',
            'type' => 'asset',
        ])->assertUnauthorized();
    }

    public function test_customer_cannot_create_account(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/admin/accounts', [
                'code' => 'TEST-01',
                'name' => 'Test Account',
                'type' => 'asset',
            ])->assertForbidden();
    }

    public function test_admin_can_create_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/accounts', [
                'code' => 'TEST-01',
                'name' => 'Test Account',
                'type' => 'asset',
                'subtype' => 'bank',
                'opening_balance' => 1000.00,
                'current_balance' => 1000.00,
                'is_active' => true,
            ])->assertCreated()
            ->assertJsonPath('data.code', 'TEST-01')
            ->assertJsonPath('data.name', 'Test Account')
            ->assertJsonPath('data.type', 'asset');

        $this->assertDatabaseHas('accounts', ['code' => 'TEST-01', 'name' => 'Test Account']);
    }

    public function test_admin_account_rejects_invalid_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/accounts', [
                'code' => 'TEST-02',
                'name' => 'Test',
                'type' => 'invalid_type',
            ])->assertUnprocessable();
    }

    public function test_admin_can_delete_account(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create(['code' => 'DEL-01', 'name' => 'Delete Me']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/accounts/{$account->code}")
            ->assertOk();

        $this->assertDatabaseMissing('accounts', ['code' => 'DEL-01']);
    }

    public function test_admin_account_listing_supports_type_filter(): void
    {
        $admin = $this->admin();

        Account::factory()->create(['code' => 'REV-01', 'name' => 'Revenue', 'type' => 'revenue']);
        Account::factory()->create(['code' => 'EXP-01', 'name' => 'Expense', 'type' => 'expense']);
        Account::factory()->create(['code' => 'ASSET-01', 'name' => 'Asset', 'type' => 'asset']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/accounts?type=expense')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('expense', $response->json('data.0.type'));
    }
}
