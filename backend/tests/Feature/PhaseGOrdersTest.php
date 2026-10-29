<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PhaseGOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(['role'=>'admin']); }

    public function test_order_report_supports_search_and_sort(): void
    {
        $admin=$this->admin(); Order::factory()->create(['number'=>'JAAJ-100001','total'=>50]); Order::factory()->create(['number'=>'JAAJ-100002','total'=>150]);
        $this->actingAs($admin,'sanctum')->getJson('/api/v1/admin/reports/orders?search=100002&sort=total&direction=asc')->assertOk()->assertJsonPath('data.data.0.number','JAAJ-100002');
    }

    public function test_auto_disburse_assigns_paid_orders_and_persists_timestamp(): void
    {
        $admin=$this->admin(); $order=Order::factory()->create(['payment_status'=>'paid','status'=>'processing']);
        $this->actingAs($admin,'sanctum')->postJson('/api/v1/admin/orders/auto-disburse',['staff_id'=>$admin->id])->assertOk()->assertJsonPath('data.count',1);
        $this->assertDatabaseHas('order_assignments',['order_id'=>$order->id,'staff_id'=>$admin->id,'status'=>'disbursed']);
        $this->assertNotNull($order->fresh()->disbursed_at);
    }

    public function test_empty_cart_recovery_is_rejected(): void
    {
        $admin=$this->admin(); $cart=Cart::create(['guest_token'=>(string)\Illuminate\Support\Str::uuid()]); $cart->update(['updated_at'=>now()->subHours(2)]);
        $this->actingAs($admin,'sanctum')->postJson("/api/v1/admin/abandoned-carts/{$cart->id}/recover")->assertUnprocessable();
    }
}
