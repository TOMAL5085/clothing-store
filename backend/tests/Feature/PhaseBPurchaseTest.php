<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_access_purchases(): void
    {
        $this->getJson('/api/v1/admin/purchases')->assertUnauthorized();
    }

    public function test_customer_cannot_access_purchases(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/purchases')
            ->assertForbidden();
    }

    public function test_admin_can_create_purchase(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/purchases', [
                'supplier_id' => $supplier->id,
                'reference_number' => 'PO-00001',
                'status' => 'ordered',
                'subtotal' => 1000.00,
                'tax' => 150.00,
                'total' => 1150.00,
                'payment_status' => 'pending',
                'notes' => 'Test purchase order',
            ])->assertCreated()
            ->assertJsonPath('data.reference_number', 'PO-00001')
            ->assertJsonPath('data.total', 1150);

        $this->assertDatabaseHas('purchases', ['reference_number' => 'PO-00001']);
    }

    public function test_admin_purchase_rejects_duplicate_reference(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();
        Purchase::factory()->create(['reference_number' => 'PO-DUP', 'supplier_id' => $supplier->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/purchases', [
                'supplier_id' => $supplier->id,
                'reference_number' => 'PO-DUP',
            ])->assertUnprocessable();
    }

    public function test_admin_can_update_purchase_status(): void
    {
        $admin = $this->admin();
        $purchase = Purchase::factory()->create(['status' => 'ordered', 'payment_status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/purchases/{$purchase->id}", [
                'status' => 'received',
                'payment_status' => 'paid',
            ])->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'received']);
    }

    public function test_admin_can_delete_purchase(): void
    {
        $admin = $this->admin();
        $purchase = Purchase::factory()->create(['reference_number' => 'PO-DELETE']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/purchases/{$purchase->id}")
            ->assertOk();

        $this->assertDatabaseMissing('purchases', ['reference_number' => 'PO-DELETE']);
    }

    public function test_admin_purchase_listing_supports_filters(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create();

        Purchase::factory()->create(['reference_number' => 'PO-A', 'supplier_id' => $supplier->id, 'status' => 'ordered']);
        Purchase::factory()->create(['reference_number' => 'PO-B', 'supplier_id' => $supplier->id, 'status' => 'received']);
        Purchase::factory()->create(['reference_number' => 'PO-C', 'supplier_id' => $supplier->id, 'status' => 'cancelled']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/purchases?status=received')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('PO-B', $response->json('data.0.reference_number'));
    }
}
