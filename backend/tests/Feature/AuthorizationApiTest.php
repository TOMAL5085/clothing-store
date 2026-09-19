<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')->getJson("/api/v1/orders/{$order->number}")->assertForbidden();
    }

    public function test_unauthenticated_orders_endpoint_is_rejected(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }
}
