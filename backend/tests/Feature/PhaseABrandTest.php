<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseABrandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_public_brand_listing_returns_active_brands(): void
    {
        Brand::factory()->create(['slug' => 'nike', 'name' => 'Nike', 'is_active' => true, 'sort_order' => 1]);
        Brand::factory()->create(['slug' => 'adidas', 'name' => 'Adidas', 'is_active' => true, 'sort_order' => 2]);
        Brand::factory()->create(['slug' => 'inactive-brand', 'name' => 'Inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/brands')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(['nike', 'adidas'], array_column($response->json('data'), 'slug'));
    }

    public function test_public_brand_show_returns_brand_and_active_products(): void
    {
        $brand = Brand::factory()->create(['slug' => 'nike', 'name' => 'Nike', 'is_active' => true]);

        $this->getJson("/api/v1/brands/{$brand->slug}")->assertOk()
            ->assertJsonPath('data.slug', 'nike')
            ->assertJsonPath('data.name', 'Nike');
    }

    public function test_public_brand_show_404_for_inactive_brand(): void
    {
        $brand = Brand::factory()->create(['slug' => 'hidden-brand', 'name' => 'Hidden', 'is_active' => false]);

        $this->getJson("/api/v1/brands/{$brand->slug}")->assertNotFound();
    }

    public function test_guest_cannot_create_brand(): void
    {
        $this->postJson('/api/v1/admin/brands', [
            'slug' => 'test-brand',
            'name' => 'Test Brand',
        ])->assertUnauthorized();
    }

    public function test_customer_cannot_create_brand(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/admin/brands', [
                'slug' => 'test-brand',
                'name' => 'Test Brand',
            ])->assertForbidden();
    }

    public function test_admin_can_create_brand(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/brands', [
                'slug' => 'test-brand',
                'name' => 'Test Brand',
                'description' => 'A test brand.',
                'sort_order' => 5,
                'is_active' => true,
            ])->assertCreated()
            ->assertJsonPath('data.slug', 'test-brand')
            ->assertJsonPath('data.name', 'Test Brand');

        $this->assertDatabaseHas('brands', ['slug' => 'test-brand', 'name' => 'Test Brand']);
    }

    public function test_admin_can_update_brand(): void
    {
        $admin = $this->admin();
        $brand = Brand::factory()->create(['slug' => 'old-slug', 'name' => 'Old Name']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/brands/{$brand->slug}", [
                'name' => 'New Name',
                'is_active' => false,
            ])->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('brands', ['slug' => 'old-slug', 'name' => 'New Name', 'is_active' => false]);
    }

    public function test_admin_can_delete_brand(): void
    {
        $admin = $this->admin();
        $brand = Brand::factory()->create(['slug' => 'delete-me', 'name' => 'Delete Me']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/brands/{$brand->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('brands', ['slug' => 'delete-me']);
    }

    public function test_admin_brand_rejects_invalid_slug(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/brands', [
                'slug' => 'Invalid Slug!',
                'name' => 'Test',
            ])->assertUnprocessable();
    }

    public function test_admin_brand_listing_supports_search_and_pagination(): void
    {
        $admin = $this->admin();

        Brand::factory()->create(['slug' => 'nike-shoes', 'name' => 'Nike Shoes']);
        Brand::factory()->create(['slug' => 'nike-clothes', 'name' => 'Nike Clothes']);
        Brand::factory()->create(['slug' => 'adidas-shoes', 'name' => 'Adidas Shoes']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/brands?q=nike')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }
}
