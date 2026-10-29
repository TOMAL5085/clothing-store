<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseELogisticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_import_courier_csv(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create();
        $file = UploadedFile::fake()->createWithContent('courier.csv', "order_number,carrier,tracking_number,status\n{$order->number},DHL,DHL-123,ready_to_ship\n");

        $this->actingAs($admin, 'sanctum')->post('/api/v1/admin/courier/bulk-import', ['file' => $file])
            ->assertOk()->assertJsonPath('data.count', 1);

        $this->assertDatabaseHas('shipments', ['order_id' => $order->id, 'tracking_number' => 'DHL-123', 'carrier' => 'DHL']);
    }

    public function test_auto_entry_creates_shipments_for_paid_orders(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['payment_status' => 'paid', 'status' => 'processing']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/courier/auto-entry', ['carrier' => 'Mock Courier'])
            ->assertOk()->assertJsonPath('data.count', 1);

        $this->assertDatabaseHas('shipments', ['order_id' => $order->id]);
    }

    public function test_courier_report_supports_status_filter_and_summary(): void
    {
        $admin = $this->admin();
        $delivered = Order::factory()->create();
        Shipment::factory()->create(['order_id' => $delivered->id, 'status' => 'delivered']);
        $pending = Order::factory()->create();
        Shipment::factory()->create(['order_id' => $pending->id, 'status' => 'pending']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/courier/reports?status=delivered')
            ->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('summary.delivered', 1);
    }
}
