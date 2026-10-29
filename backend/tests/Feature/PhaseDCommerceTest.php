<?php

namespace Tests\Feature;

use App\Models\WeightPriceRule;
use App\Models\WholesaleCustomer;
use App\Models\WholesaleCustomerLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseDCommerceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_wholesale_customer_and_ledger_entry(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/wholesale-customers', [
            'code' => 'WHOLESALE-001', 'name' => 'Northside Retail', 'company' => 'Northside Ltd', 'credit_limit' => 50000,
        ])->assertCreated();

        $customerId = $response->json('data.id');
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/wholesale-customers/{$customerId}/ledger", [
            'entry_type' => 'debit', 'amount' => 1250, 'reference' => 'INV-1001', 'entry_date' => '2026-10-29',
        ])->assertCreated();

        $this->assertDatabaseHas('wholesale_customer_ledger', ['wholesale_customer_id' => $customerId, 'amount' => 1250]);
    }

    public function test_wholesale_customer_listing_includes_signed_balance(): void
    {
        $admin = $this->admin();
        $customer = WholesaleCustomer::create(['code' => 'WHOLESALE-002', 'name' => 'Balance Test']);
        WholesaleCustomerLedger::create(['wholesale_customer_id' => $customer->id, 'entry_type' => 'debit', 'amount' => 100, 'entry_date' => '2026-10-29']);
        WholesaleCustomerLedger::create(['wholesale_customer_id' => $customer->id, 'entry_type' => 'credit', 'amount' => 25, 'entry_date' => '2026-10-29']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/wholesale-customers')->assertOk()->assertJsonPath('data.data.0.balance', 75);
    }

    public function test_weight_quote_prefers_product_specific_rule(): void
    {
        $global = WeightPriceRule::create(['min_weight' => 0, 'max_weight' => 10, 'price' => 100, 'unit' => 'kg', 'is_active' => true]);
        $product = \App\Models\Product::factory()->create();
        WeightPriceRule::create(['product_id' => $product->id, 'min_weight' => 0, 'max_weight' => 10, 'price' => 125, 'unit' => 'kg', 'is_active' => true]);

        $this->postJson('/api/v1/weight-price/quote', ['weight' => 2, 'product_id' => $product->id])->assertOk()->assertJsonPath('data.price', '125.00');
        $this->assertDatabaseHas('weight_price_rules', ['id' => $global->id]);
    }

    public function test_weight_quote_rejects_negative_weight(): void
    {
        $this->postJson('/api/v1/weight-price/quote', ['weight' => -1])->assertUnprocessable();
    }
}
