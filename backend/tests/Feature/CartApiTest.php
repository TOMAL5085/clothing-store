<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_item_validates_stock(): void
    {
        $product = Product::factory()->create(['external_id' => 'coat']);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 1, 'is_active' => true]);

        $this->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_add_item_returns_authoritative_totals(): void
    {
        $product = Product::factory()->create(['external_id' => 'coat', 'price' => 100]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => 'Coat', 'sort_order' => 1]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);

        $this->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.summary.subtotal', 200);
    }

    public function test_cart_keeps_same_size_different_colors_as_separate_variant_lines(): void
    {
        $product = Product::factory()->create(['external_id' => 'shirt', 'price' => 100]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $ink = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $bone = Color::firstOrCreate(['name' => 'Bone'], ['hex' => '#e6dfd0']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => 'Shirt', 'sort_order' => 1]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $ink->id, 'sku' => 'SHIRT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $bone->id, 'sku' => 'SHIRT-M-BONE', 'price' => 110, 'stock_quantity' => 10, 'is_active' => true]);

        $token = $this->postJson('/api/v1/cart/items', ['product_id' => 'shirt', 'size' => 'M', 'color' => 'Ink', 'quantity' => 1])
            ->assertCreated()
            ->json('data.token');

        $this->postJson('/api/v1/cart/items', ['cart_token' => $token, 'product_id' => 'shirt', 'size' => 'M', 'color' => 'Bone', 'quantity' => 1])
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.summary.subtotal', 210);
    }

    public function test_coupon_validation_and_removal_are_server_authoritative(): void
    {
        Coupon::factory()->create(['code' => 'JAAJ10', 'type' => 'percent', 'value' => 10, 'minimum_order_amount' => 200]);
        $product = Product::factory()->create(['external_id' => 'coat', 'price' => 100]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => 'Coat', 'sort_order' => 1]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);

        $token = $this->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 1])->json('data.token');

        $this->postJson('/api/v1/cart/promo', ['cart_token' => $token, 'promo_code' => 'JAAJ10'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('promo_code');

        $line = $this->getJson("/api/v1/cart?cart_token={$token}")->json('data.items.0.id');
        $this->patchJson('/api/v1/cart/items/'.urlencode($line), ['cart_token' => $token, 'quantity' => 2])->assertOk();

        $this->postJson('/api/v1/cart/promo', ['cart_token' => $token, 'promo_code' => 'JAAJ10'])
            ->assertOk()
            ->assertJsonPath('data.summary.subtotal', 200)
            ->assertJsonPath('data.summary.discount', 20)
            ->assertJsonPath('data.coupon.code', 'JAAJ10');

        $this->postJson('/api/v1/cart/promo', ['cart_token' => $token, 'promo_code' => null])
            ->assertOk()
            ->assertJsonPath('data.summary.discount', 0)
            ->assertJsonPath('data.promo', null);
    }

    public function test_coupon_usage_limit_is_enforced_for_authenticated_customer(): void
    {
        Coupon::factory()->create(['code' => 'JAAJ10', 'type' => 'percent', 'value' => 10, 'per_customer_limit' => 1]);
        $user = User::factory()->create();
        $product = Product::factory()->create(['external_id' => 'coat', 'price' => 100, 'stock_quantity' => 10]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => 'Coat', 'sort_order' => 1]);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 1])->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/promo', ['promo_code' => 'JAAJ10'])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/checkout/orders', $this->payload())->assertCreated();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 1])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/promo', ['promo_code' => 'JAAJ10'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('promo_code');
    }

    private function payload(): array
    {
        return [
            'delivery_method' => 'standard',
            'shipping_address' => [
                'firstName' => 'Jane',
                'lastName' => 'Member',
                'email' => 'jane@example.test',
                'address' => '1 Main Street',
                'city' => 'Copenhagen',
                'postalCode' => '1000',
                'country' => 'Denmark',
            ],
            'payment' => [
                'card_name' => 'Jane Member',
                'card_number' => '4242 4242 4242 4242',
                'expiry' => '12/30',
                'cvc' => '123',
            ],
        ];
    }
}
