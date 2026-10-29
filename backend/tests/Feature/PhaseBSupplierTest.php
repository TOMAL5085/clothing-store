<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBSupplierTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_supplier_listing_returns_active(): void
    {
        Supplier::factory()->create(['slug' => 'acme', 'name' => 'Acme Corp', 'status' => 'active']);
        Supplier::factory()->create(['slug' => 'beta', 'name' => 'Beta Ltd', 'status' => 'active']);
        Supplier::factory()->create(['slug' => 'hidden', 'name' => 'Hidden Co', 'status' => 'inactive']);

        $response = $this->getJson('/api/v1/suppliers')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $slugs = array_column($response->json('data'), 'slug');
        $this->assertContains('acme', $slugs);
        $this->assertContains('beta', $slugs);
        $this->assertNotContains('hidden', $slugs);
    }

    public function test_public_supplier_show_404_for_inactive(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'hidden-co', 'name' => 'Hidden Co', 'status' => 'inactive']);

        $this->getJson("/api/v1/suppliers/{$supplier->slug}")->assertNotFound();
    }

    public function test_guest_cannot_create_supplier(): void
    {
        $this->postJson('/api/v1/admin/suppliers', [
            'name' => 'Test Supplier',
            'slug' => 'test-supplier',
        ])->assertUnauthorized();
    }

    public function test_admin_can_create_supplier(): void
    {
        $admin = $this->admin();
        $zone = Zone::factory()->create();
        $city = City::factory()->create(['zone_id' => $zone->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/suppliers', [
                'name' => 'Test Supplier',
                'slug' => 'test-supplier',
                'contact_person' => 'John Doe',
                'email' => 'john@test.com',
                'phone' => '+1234567890',
                'address' => '123 Main St',
                'city_id' => $city->id,
                'zone_id' => $zone->id,
                'status' => 'active',
            ])->assertCreated()
            ->assertJsonPath('data.slug', 'test-supplier')
            ->assertJsonPath('data.name', 'Test Supplier');

        $this->assertDatabaseHas('suppliers', ['slug' => 'test-supplier', 'name' => 'Test Supplier']);
    }

    public function test_admin_supplier_rejects_duplicate_slug(): void
    {
        $admin = $this->admin();
        Supplier::factory()->create(['slug' => 'existing', 'name' => 'Existing']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/suppliers', [
                'name' => 'Duplicate',
                'slug' => 'existing',
            ])->assertUnprocessable();
    }

    public function test_admin_can_delete_supplier(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::factory()->create(['slug' => 'delete-me', 'name' => 'Delete Me']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/suppliers/{$supplier->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('suppliers', ['slug' => 'delete-me']);
    }

    public function test_admin_supplier_listing_supports_search_and_status_filter(): void
    {
        $admin = $this->admin();

        Supplier::factory()->create(['slug' => 'acme-shoes', 'name' => 'Acme Shoes', 'status' => 'active']);
        Supplier::factory()->create(['slug' => 'acme-clothes', 'name' => 'Acme Clothes', 'status' => 'active']);
        Supplier::factory()->create(['slug' => 'beta-ltd', 'name' => 'Beta Ltd', 'status' => 'inactive']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/suppliers?q=acme')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/suppliers?status=inactive')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('beta-ltd', $response->json('data.0.slug'));
    }
}
