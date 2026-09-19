<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_and_decrements_inventory(): void
    {
        Coupon::factory()->create(['code' => 'JAAJ10']);
        $product = Product::factory()->create(['external_id' => 'coat', 'price' => 100, 'stock_quantity' => 10]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->images()->create(['url' => '/imagery/hero-primary.jpg', 'alt' => 'Coat', 'sort_order' => 1]);
        $variant = $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);

        $cart = $this->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 2])->json('data.token');
        $this->postJson('/api/v1/cart/promo', ['cart_token' => $cart, 'promo_code' => 'JAAJ10'])->assertOk();

        $this->postJson('/api/v1/checkout/orders', $this->payload($cart))
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount', 20);

        $this->assertDatabaseHas('orders', ['subtotal' => 200, 'discount' => 20]);
        $this->assertSame(8, $variant->refresh()->stock_quantity);
    }

    public function test_declined_demo_card_returns_validation_error(): void
    {
        $product = Product::factory()->create(['external_id' => 'coat', 'price' => 100, 'stock_quantity' => 10]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $product->variants()->create(['size_id' => $size->id, 'color_id' => $color->id, 'sku' => 'COAT-M-INK', 'price' => 100, 'stock_quantity' => 10, 'is_active' => true]);
        $cart = $this->postJson('/api/v1/cart/items', ['product_id' => 'coat', 'size' => 'M', 'quantity' => 1])->json('data.token');
        $payload = $this->payload($cart);
        $payload['payment']['card_number'] = '4242 4242 4242 0000';

        $this->postJson('/api/v1/checkout/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment.card_number');
    }

    private function payload(string $cart): array
    {
        return [
            'cart_token' => $cart,
            'promo_code' => 'JAAJ10',
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
