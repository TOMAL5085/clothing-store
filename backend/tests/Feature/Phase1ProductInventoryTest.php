<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ProductInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_filters_sorting_and_pagination_are_backend_powered(): void
    {
        $men = Category::factory()->create(['slug' => 'men', 'label' => 'Men']);
        $women = Category::factory()->create(['slug' => 'women', 'label' => 'Women']);

        $this->product([
            'category_id' => $men->id,
            'external_id' => 'black-shirt',
            'slug' => 'black-shirt',
            'name' => 'Black Studio Shirt',
            'description' => 'Organic cotton shirt',
            'price' => 120,
            'sku' => 'BLACK-SHIRT',
        ], 'L', 'Ink', 5);

        $this->product([
            'category_id' => $men->id,
            'external_id' => 'black-blazer',
            'slug' => 'black-blazer',
            'name' => 'Black Blazer',
            'description' => 'Tailored wool blazer',
            'price' => 300,
            'sku' => 'BLACK-BLAZER',
        ], 'L', 'Ink', 5);

        $this->product([
            'category_id' => $women->id,
            'external_id' => 'white-shirt',
            'slug' => 'white-shirt',
            'name' => 'White Shirt',
            'description' => 'Poplin shirt',
            'price' => 100,
            'sku' => 'WHITE-SHIRT',
        ], 'S', 'Chalk', 5);

        $this->getJson('/api/v1/products?q=black&category=men&sizes[]=L&colors[]=Ink&min_price=100&max_price=350&sort=price-desc&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'black-blazer')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_admin_can_adjust_variant_inventory_and_negative_stock_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['external_id' => 'coat', 'slug' => 'coat', 'stock_quantity' => 2], 'M', 'Ink', 2);
        $variant = $product->variants()->first();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->slug}/inventory", [
                'variant_id' => $variant->id,
                'mode' => 'increase',
                'quantity' => 3,
                'low_stock_threshold' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('data.stockQuantity', 5)
            ->assertJsonPath('data.inventoryStatus', 'low_stock');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->slug}/inventory", [
                'variant_id' => $variant->id,
                'mode' => 'decrease',
                'quantity' => 99,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_admin_product_index_includes_inactive_products_and_requires_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->product(['external_id' => 'active-coat', 'slug' => 'active-coat', 'name' => 'Active Coat'], 'M', 'Ink', 3);
        $this->product(['external_id' => 'inactive-coat', 'slug' => 'inactive-coat', 'name' => 'Inactive Coat', 'is_active' => false], 'L', 'Ink', 0);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/products?status=all')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['id' => 'inactive-coat']);
    }

    public function test_non_admin_cannot_manage_inventory(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product(['external_id' => 'coat', 'slug' => 'coat'], 'M', 'Ink', 2);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->slug}/inventory", [
                'mode' => 'set',
                'quantity' => 5,
            ])
            ->assertForbidden();
    }

    private function product(array $attributes, string $sizeName, string $colorName, int $stock): Product
    {
        $product = Product::factory()->create([...$attributes, 'stock_quantity' => $stock, 'in_stock' => $stock > 0]);
        $size = Size::firstOrCreate(['name' => $sizeName], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => $colorName], ['hex' => $colorName === 'Ink' ? '#1c1a17' : '#f4f2ec']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => $product->name, 'sort_order' => 1]);
        $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => $product->sku.'-'.$sizeName.'-'.$colorName,
            'price' => $product->price,
            'stock_quantity' => $stock,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        return $product;
    }
}
