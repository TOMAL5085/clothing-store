<?php

namespace Tests\Feature;

use App\Models\ManualPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function validPayload(): array
    {
        return [
            'reference_number' => 'MAN-00001',
            'account_id' => Account::factory()->create()->id,
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'payment_method' => 'cash',
            'amount' => 250.00,
            'currency' => 'USD',
            'payment_date' => '2026-10-29',
            'status' => 'pending',
            'transaction_id' => 'TXN-12345',
            'notes' => 'Cash payment for order',
        ];
    }

    public function test_guest_cannot_access_manual_payments(): void
    {
        $this->getJson('/api/v1/admin/manual-payments')->assertUnauthorized();
    }

    public function test_admin_can_create_manual_payment(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/manual-payments', $this->validPayload())->assertCreated()
            ->assertJsonPath('data.reference_number', 'MAN-00001')
            ->assertJsonPath('data.amount', 250);

        $this->assertDatabaseHas('manual_payments', ['reference_number' => 'MAN-00001']);
    }

    public function test_admin_manual_payment_rejects_duplicate_reference(): void
    {
        $admin = $this->admin();
        ManualPayment::factory()->create(['reference_number' => 'MAN-DUP']);

        $payload = $this->validPayload();
        $payload['reference_number'] = 'MAN-DUP';

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/manual-payments', $payload)->assertUnprocessable();
    }

    public function test_admin_can_update_manual_payment_status(): void
    {
        $admin = $this->admin();
        $payment = ManualPayment::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/manual-payments/{$payment->reference_number}", [
                'status' => 'verified',
            ])->assertOk()
            ->assertJsonPath('data.status', 'verified');

        $this->assertDatabaseHas('manual_payments', ['reference_number' => $payment->reference_number, 'status' => 'verified']);
    }

    public function test_admin_can_delete_manual_payment(): void
    {
        $admin = $this->admin();
        $payment = ManualPayment::factory()->create(['reference_number' => 'MAN-DELETE']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/manual-payments/{$payment->reference_number}")
            ->assertOk();

        $this->assertDatabaseMissing('manual_payments', ['reference_number' => 'MAN-DELETE']);
    }

    public function test_admin_manual_payment_listing_supports_filters(): void
    {
        $admin = $this->admin();

        ManualPayment::factory()->create(['reference_number' => 'MAN-A', 'payment_method' => 'cash', 'status' => 'pending']);
        ManualPayment::factory()->create(['reference_number' => 'MAN-B', 'payment_method' => 'bank_transfer', 'status' => 'verified']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/manual-payments?payment_method=cash')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('cash', $response->json('data.0.payment_method'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/manual-payments?status=verified')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('MAN-B', $response->json('data.0.reference_number'));
    }
}
