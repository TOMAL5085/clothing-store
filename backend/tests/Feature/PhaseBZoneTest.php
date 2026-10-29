<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBZoneTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_zone_listing_returns_active_zones(): void
    {
        Zone::factory()->create(['code' => 'ASIA', 'name' => 'Asia', 'is_active' => true, 'sort_order' => 1]);
        Zone::factory()->create(['code' => 'EU', 'name' => 'Europe', 'is_active' => true, 'sort_order' => 2]);
        Zone::factory()->create(['code' => 'INACTIVE', 'name' => 'Inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/zones')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(['ASIA', 'EU'], array_column($response->json('data'), 'code'));
    }

    public function test_public_zone_show_404_for_inactive(): void
    {
        $zone = Zone::factory()->create(['code' => 'HIDDEN', 'name' => 'Hidden', 'is_active' => false]);

        $this->getJson("/api/v1/zones/{$zone->code}")->assertNotFound();
    }

    public function test_guest_cannot_create_zone(): void
    {
        $this->postJson('/api/v1/admin/zones', [
            'name' => 'Test Zone',
            'code' => 'TEST',
        ])->assertUnauthorized();
    }

    public function test_customer_cannot_create_zone(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/admin/zones', [
                'name' => 'Test Zone',
                'code' => 'TEST',
            ])->assertForbidden();
    }

    public function test_admin_can_create_zone(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/zones', [
                'name' => 'Test Zone',
                'code' => 'TESTZ',
                'shipping_cost' => 15.50,
                'sort_order' => 3,
                'is_active' => true,
            ])->assertCreated()
            ->assertJsonPath('data.code', 'TESTZ')
            ->assertJsonPath('data.name', 'Test Zone');

        $this->assertDatabaseHas('zones', ['code' => 'TESTZ', 'name' => 'Test Zone']);
    }

    public function test_admin_zone_rejects_invalid_code(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/zones', [
                'name' => 'Test',
                'code' => 'lowercase',
            ])->assertUnprocessable();
    }

    public function test_admin_zone_listing_supports_search(): void
    {
        $admin = $this->admin();

        Zone::factory()->create(['code' => 'ASIA', 'name' => 'Asia Pacific']);
        Zone::factory()->create(['code' => 'EU', 'name' => 'European Union']);
        Zone::factory()->create(['code' => 'NA', 'name' => 'North America']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/zones?q=asia')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Asia Pacific', $response->json('data.0.name'));
    }

    public function test_admin_can_delete_zone(): void
    {
        $admin = $this->admin();
        $zone = Zone::factory()->create(['code' => 'DEL', 'name' => 'Delete Me']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/zones/{$zone->code}")
            ->assertOk();

        $this->assertDatabaseMissing('zones', ['code' => 'DEL']);
    }
}
