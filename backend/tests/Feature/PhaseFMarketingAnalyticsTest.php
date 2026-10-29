<?php

namespace Tests\Feature;

use App\Models\FraudCheck;
use App\Models\Order;
use App\Models\User;
use App\Models\VisitorCount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFMarketingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_visitor_tracking_deduplicates_an_ip_for_daily_and_monthly_counts(): void
    {
        $this->postJson('/api/v1/visitors', ['path' => '/shop'])->assertOk();
        $this->postJson('/api/v1/visitors', ['path' => '/cart'])->assertOk();

        $this->assertSame(1, VisitorCount::where('period', 'daily')->count());
        $this->assertSame(1, VisitorCount::where('period', 'monthly')->count());
    }

    public function test_admin_can_filter_fraud_report(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create();
        FraudCheck::create(['order_id' => $order->id, 'risk_level' => 'high', 'risk_score' => 80, 'signals' => ['payment_failed'], 'status' => 'reviewed']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/marketing/fraud-report?risk_level=high')
            ->assertOk()->assertJsonPath('data.data.0.risk_level', 'high');
    }

    public function test_admin_can_save_public_marketing_settings(): void
    {
        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/admin/store-settings', [
            'key' => 'google_analytics_id', 'value' => 'G-ABC123', 'type' => 'string', 'is_public' => true,
        ])->assertOk();

        $this->getJson('/api/v1/store-settings')->assertOk()->assertJsonPath('data.google_analytics_id', 'G-ABC123');
    }
}
