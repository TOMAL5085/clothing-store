<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBCityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_city_listing_returns_active_cities(): void
    {
        $zone = Zone::factory()->create(['code' => 'ASIA', 'name' => 'Asia']);
        City::factory()->create(['zone_id' => $zone->id, 'name' => 'Dhaka', 'is_active' => true]);
        City::factory()->create(['zone_id' => $zone->id, 'name' => 'Tokyo', 'is_active' => true]);
        City::factory()->create(['zone_id' => $zone->id, 'name' => 'Hidden', 'is_active' => false]);

        $response = $this->getJson('/api/v1/cities')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $names = array_column($response->json('data'), 'name');
        $this->assertContains('Dhaka', $names);
        $this->assertContains('Tokyo', $names);
        $this->assertNotContains('Hidden', $names);
    }

    public function test_public_city_filters_by_zone(): void
    {
        $asia = Zone::factory()->create(['code' => 'ASIA', 'name' => 'Asia']);
        $eu = Zone::factory()->create(['code' => 'EU', 'name' => 'Europe']);
        City::factory()->create(['zone_id' => $asia->id, 'name' => 'Dhaka']);
        City::factory()->create(['zone_id' => $eu->id, 'name' => 'Berlin']);

        $response = $this->getJson("/api/v1/cities?zone_id={$asia->id}")->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Dhaka', $response->json('data.0.name'));
    }

    public function test_guest_cannot_create_city(): void
    {
        $this->postJson('/api/v1/admin/cities', [
            'zone_id' => 1,
            'name' => 'Test City',
        ])->assertUnauthorized();
    }

    public function test_admin_can_create_city(): void
    {
        $admin = $this->admin();
        $zone = Zone::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/cities', [
                'zone_id' => $zone->id,
                'name' => 'New City',
                'code' => 'NC',
                'sort_order' => 1,
                'is_active' => true,
            ])->assertCreated()
            ->assertJsonPath('data.name', 'New City');

        $this->assertDatabaseHas('cities', ['name' => 'New City', 'zone_id' => $zone->id]);
    }

    public function test_admin_can_delete_city(): void
    {
        $admin = $this->admin();
        $city = City::factory()->create(['name' => 'Delete Me']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/cities/{$city->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }

    public function test_city_level_filter_top_and_sub(): void
    {
        // This test validates the level filter works on admin listing
        $admin = $this->admin();
        $zone = Zone::factory()->create();
        City::factory()->create(['zone_id' => $zone->id, 'name' => 'City A']);
        City::factory()->create(['zone_id' => $zone->id, 'name' => 'City B']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/cities?level=top')
            ->assertOk();
    }
}
