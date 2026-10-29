<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBSupplierLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_access_supplier_ledger(): void
    {
        $this->getJson('/api/v1/admin/supplier-ledger')->assertUnauthorized();
    }

    public function test_admin_can_access_supplier_ledger(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();

        SupplierLedger::factory()->create([
            'supplier_id' => $supplier->id,
            'reference_type' => 'purchase',
            'reference_id' => 1,
            'debit' => 500.00,
            'credit' => 0.00,
            'balance' => 500.00,
            'description' => 'Initial purchase',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/supplier-ledger')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(500.00, (float) $response->json('data.0.balance'));
    }

    public function test_admin_can_create_ledger_entry(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/supplier-ledger', [
                'supplier_id' => $supplier->id,
                'reference_type' => 'payment',
                'reference_id' => 100,
                'debit' => 0.00,
                'credit' => 200.00,
                'description' => 'Payment made',
            ])->assertCreated()
            ->assertJsonPath('data.balance', -200);

        $this->assertDatabaseHas('supplier_ledger', [
            'supplier_id' => $supplier->id,
            'reference_type' => 'payment',
            'credit' => 200.00,
        ]);
    }

    public function test_ledger_balance_calculates_cumulatively(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();

        SupplierLedger::factory()->create([
            'supplier_id' => $supplier->id,
            'reference_type' => 'purchase',
            'reference_id' => 1,
            'debit' => 1000.00,
            'credit' => 0.00,
            'balance' => 1000.00,
            'description' => 'First entry',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/supplier-ledger', [
                'supplier_id' => $supplier->id,
                'reference_type' => 'payment',
                'reference_id' => 2,
                'debit' => 0.00,
                'credit' => 400.00,
                'description' => 'Payment',
            ])->assertCreated()
            ->assertJsonPath('data.balance', 600);
    }

    public function test_admin_cannot_update_ledger_entry(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();
        $ledger = SupplierLedger::factory()->create(['supplier_id' => $supplier->id]);

        // No update route exists for ledger entries by design
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/supplier-ledger/{$ledger->id}", [
                'description' => 'Modified',
            ]);

        // Should be 404 since no update route is registered
        $this->assertTrue($response->status() === 404 || $response->status() === 405);
    }

    public function test_ledger_listing_filters_by_supplier(): void
    {
        $admin = $this->admin();
        $supplierA = Supplier::factory()->create();
        $supplierB = Supplier::factory()->create();

        SupplierLedger::factory()->create(['supplier_id' => $supplierA->id, 'balance' => 100.00]);
        SupplierLedger::factory()->create(['supplier_id' => $supplierB->id, 'balance' => 200.00]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/supplier-ledger?supplier_id={$supplierA->id}")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(100.00, (float) $response->json('data.0.balance'));
    }
}
