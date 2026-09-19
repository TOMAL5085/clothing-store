<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_view_and_update_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Jane Member']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->number)
            ->assertJsonPath('data.0.paymentStatus', 'paid');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/orders/'.$order->number)
            ->assertOk()
            ->assertJsonPath('data.statusCode', 'processing');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/orders/'.$order->number.'/status', ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('data.statusCode', 'shipped')
            ->assertJsonPath('data.status', 'Shipped');
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/admin/orders')->assertForbidden();
        $this->actingAs($customer, 'sanctum')
            ->patchJson('/api/v1/admin/orders/'.$order->number.'/status', ['status' => 'shipped'])
            ->assertForbidden();
    }
}
