<?php

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4ResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_cancellation_and_admin_approval_cancels_order_with_pending_refund(): void
    {
        [$order, $variant, $product] = $this->paidOrderWithItem(status: 'processing', quantity: 2);
        $customer = $order->user;
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'I ordered the wrong size.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Duplicate request.'])
            ->assertUnprocessable();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/cancellations/'.CancellationRequest::first()->id, [
                'decision' => 'approved',
                'admin_reason' => 'Approved before shipment.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'executed')
            ->assertJsonPath('data.refund.status', 'pending');

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->inventory_decremented_at);
        $this->assertSame(10, $variant->refresh()->stock_quantity);
        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => 'pending',
            'reason' => 'order_cancellation',
        ]);
    }

    public function test_cancellation_is_blocked_after_shipment_leaves_warehouse_and_cross_customer_is_forbidden(): void
    {
        [$order] = $this->paidOrderWithItem(status: 'shipped');
        $order->shipment()->create(['status' => 'shipped', 'carrier' => 'Mock Courier']);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Please cancel this order.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Not my order.'])
            ->assertForbidden();
    }

    public function test_delivered_order_return_request_admin_review_received_and_refund_lifecycle(): void
    {
        [$order] = $this->paidOrderWithItem(status: 'delivered', quantity: 3);
        $admin = User::factory()->create(['role' => 'admin']);
        $item = $order->items()->firstOrFail();

        $created = $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'The item arrived damaged.',
                'items' => [
                    ['order_item_id' => $item->id, 'quantity' => 2],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 2);

        $returnId = $created->json('data.id');

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Trying to over return.',
                'items' => [
                    ['order_item_id' => $item->id, 'quantity' => 2],
                ],
            ])
            ->assertUnprocessable();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $received = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/returns/{$returnId}/received", ['admin_reason' => 'Package received.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.refund.status', 'pending');

        $refundId = $received->json('data.refund.id');
        $this->assertDatabaseHas('refunds', [
            'id' => $refundId,
            'amount' => '200.00',
            'reason' => 'return',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", [
                'status' => 'succeeded',
                'provider_reference' => 'rf_test_123',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'succeeded');

        $this->assertSame('resolved', ReturnRequest::find($returnId)->status);
        $this->assertSame('succeeded', Refund::find($refundId)->status);
    }

    public function test_return_requires_delivered_owned_order_and_valid_order_items(): void
    {
        [$order] = $this->paidOrderWithItem(status: 'processing');
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Not eligible yet.',
                'items' => [['order_item_id' => $order->items()->first()->id, 'quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');

        $order->update(['status' => 'delivered']);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Not my order.',
                'items' => [['order_item_id' => $order->items()->first()->id, 'quantity' => 1]],
            ])
            ->assertForbidden();

        $this->actingAs($order->user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Invalid item.',
                'items' => [['order_item_id' => 999999, 'quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_customer_cannot_access_admin_resolution_endpoints(): void
    {
        [$order] = $this->paidOrderWithItem(status: 'processing');
        $cancellation = CancellationRequest::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'status' => 'pending',
            'reason' => 'Please cancel.',
            'requested_at' => now(),
        ]);

        $this->actingAs($order->user, 'sanctum')
            ->getJson('/api/v1/admin/cancellations')
            ->assertForbidden();

        $this->actingAs($order->user, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellation->id}", ['decision' => 'approved'])
            ->assertForbidden();
    }

    /**
     * @return array{0: Order, 1: \App\Models\ProductVariant, 2: Product}
     */
    private function paidOrderWithItem(string $status = 'processing', int $quantity = 2): array
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 10 - $quantity,
            'in_stock' => true,
            'is_active' => true,
        ]);
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $variant = $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'RES-M-INK',
            'price' => 100,
            'stock_quantity' => 10 - $quantity,
            'is_active' => true,
        ]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
            'currency' => 'USD',
            'subtotal' => 100 * $quantity,
            'shipping' => 0,
            'total' => 100 * $quantity,
            'inventory_decremented_at' => now(),
        ]);
        $order->payment()->create([
            'provider' => 'demo',
            'status' => 'paid',
            'reference' => 'demo_'.strtolower(fake()->bothify('????####')),
            'amount' => $order->total,
            'amount_minor' => (int) ((float) $order->total * 100),
            'currency' => 'USD',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => 100,
            'line_total' => 100 * $quantity,
        ]);

        return [$order->fresh(['user', 'items']), $variant, $product];
    }
}
