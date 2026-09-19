<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_listing_supports_category_filter(): void
    {
        $women = Category::factory()->create(['slug' => 'women']);
        $men = Category::factory()->create(['slug' => 'men']);
        $this->product(['category_id' => $women->id, 'external_id' => 'coat', 'slug' => 'coat']);
        $this->product(['category_id' => $men->id, 'external_id' => 'hoodie', 'slug' => 'hoodie']);

        $this->getJson('/api/v1/products?category=women')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'coat');
    }

    public function test_product_details_use_slug(): void
    {
        $this->product(['external_id' => 'coat', 'slug' => 'adele-trench-coat']);

        $this->getJson('/api/v1/products/adele-trench-coat')
            ->assertOk()
            ->assertJsonPath('data.slug', 'adele-trench-coat')
            ->assertJsonStructure(['data' => ['colors', 'sizes', 'images']]);
    }

    private function product(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => $product->name, 'sort_order' => 1]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => $product->sku.'-M-INK', 'price' => $product->price, 'stock_quantity' => 10, 'is_active' => true]);

        return $product;
    }
}
