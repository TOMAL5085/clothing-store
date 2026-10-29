<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseASubCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_public_categories_returns_top_level_when_no_parent_id(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men', 'parent_id' => null]);
        Category::factory()->create(['slug' => 'shirts', 'label' => 'Shirts', 'parent_id' => $men->id]);
        Category::factory()->create(['slug' => 'women', 'label' => 'Women', 'parent_id' => null]);

        $response = $this->getJson('/api/v1/categories')->assertOk();

        $slugs = array_column($response->json('data'), 'slug');
        sort($slugs);
        $this->assertSame(['men', 'women'], $slugs);
        $this->assertNotContains('shirts', $slugs);
    }

    public function test_public_categories_filters_by_parent_id(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men', 'parent_id' => null]);
        $shirts = Category::factory()->create(['slug' => 'shirts', 'label' => 'Shirts', 'parent_id' => $men->id]);
        Category::factory()->create(['slug' => 't-shirts', 'label' => 'T-Shirts', 'parent_id' => $shirts->id]);
        Category::factory()->create(['slug' => 'women', 'label' => 'Women', 'parent_id' => null]);

        $response = $this->getJson('/api/v1/categories?parent_id='.$men->id)->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('shirts', $response->json('data.0.slug'));
    }

    public function test_public_categories_includes_parent_info_in_resource(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men', 'parent_id' => null]);
        $shirts = Category::factory()->create(['slug' => 'shirts', 'label' => 'Shirts', 'parent_id' => $men->id]);

        $response = $this->getJson("/api/v1/categories?parent_id={$men->id}")->assertOk();

        $this->assertNotNull($response->json('data.0.parent'));
        $this->assertSame('men', $response->json('data.0.parent.slug'));
        $this->assertSame('Men', $response->json('data.0.parent.label'));
    }

    public function test_category_model_has_parent_and_children_relationships(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men']);
        $shirts = Category::factory()->create(['slug' => 'shirts', 'label' => 'Shirts', 'parent_id' => $men->id]);

        $this->assertSame($men->id, $shirts->parent->id);
        $this->assertTrue($men->children()->where('id', $shirts->id)->exists());
    }

    public function test_inactive_category_is_not_listed_even_as_child(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men', 'is_active' => true]);
        Category::factory()->create(['slug' => 'shirts', 'label' => 'Shirts', 'parent_id' => $men->id, 'is_active' => false]);

        $response = $this->getJson("/api/v1/categories?parent_id={$men->id}")->assertOk();

        $this->assertCount(0, $response->json('data'));
    }
}
