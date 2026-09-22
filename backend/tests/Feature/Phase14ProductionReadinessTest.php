<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\CmsContent;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase14ProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new \RuntimeException('Phase 14 probe failure.');
    }
}

class Phase14ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'status' => 'active']);
    }

    /* ------------------------------------------------------------------ *
     * Health and request identity
     * ------------------------------------------------------------------ */

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_request_ids_are_preserved_on_api_responses(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $requestId = $response->headers->get('X-Request-ID');
        $this->assertNotEmpty($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $requestId
        );
    }

    /* ------------------------------------------------------------------ *
     * Deployment validation command
     * ------------------------------------------------------------------ */

    public function test_app_check_detects_debug_in_production(): void
    {
        config(['app.env' => 'production', 'app.debug' => true]);

        $this->artisan('app:check')->assertFailed();
    }

    public function test_app_check_detects_localhost_urls_in_production(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'http://localhost:8000',
        ]);

        $this->artisan('app:check')->assertFailed();
    }

    public function test_app_check_rejects_invalid_app_url(): void
    {
        config(['app.url' => 'not-a-url']);

        $this->artisan('app:check')->assertFailed();
    }

    public function test_app_check_rejects_unknown_broadcast_driver(): void
    {
        config(['broadcasting.default' => 'bogus-driver']);

        $this->artisan('app:check')->assertFailed();
    }

    /* ------------------------------------------------------------------ *
     * Security posture
     * ------------------------------------------------------------------ */

    public function test_admin_cms_endpoints_remain_protected(): void
    {
        $content = CmsContent::factory()->create(['status' => 'published']);

        // Unauthenticated first: actingAs persists for later requests in this test.
        $this->postJson('/api/v1/admin/banners', ['image_path' => 'https://example.com/a.jpg'])
            ->assertUnauthorized();

        $this->actingAs($this->customer(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', ['key' => 'x', 'type' => 'promo', 'title' => 'X'])
            ->assertForbidden();

        $this->actingAs($this->customer(), 'sanctum')
            ->deleteJson("/api/v1/admin/cms/content/{$content->id}")
            ->assertForbidden();
    }

    public function test_cms_banner_visibility_remains_protected(): void
    {
        CmsContent::factory()->create(['key' => 'draft-block', 'status' => 'draft']);
        Banner::factory()->create(['key' => 'draft-slide', 'status' => 'draft']);

        $contentKeys = array_column($this->getJson('/api/v1/cms/content')->json('data'), 'key');
        $bannerKeys = array_column($this->getJson('/api/v1/banners')->json('data'), 'key');

        $this->assertNotContains('draft-block', $contentKeys);
        $this->assertNotContains('draft-slide', $bannerKeys);
    }

    public function test_payment_secrets_absent_from_api_responses(): void
    {
        $product = Product::factory()->create();

        $listBody = strtolower($this->getJson('/api/v1/products')->assertOk()->getContent());
        $detailBody = strtolower($this->getJson("/api/v1/products/{$product->slug}")->assertOk()->getContent());

        foreach (['stripe_secret', 'stripe_key', 'webhook_secret', 'sslcommerz_store_password', 'card_number', 'checkout_token'] as $needle) {
            $this->assertStringNotContainsString($needle, $listBody, "Leak in product list: {$needle}");
            $this->assertStringNotContainsString($needle, $detailBody, "Leak in product detail: {$needle}");
        }
    }

    public function test_login_rate_limit_remains_active(): void
    {
        $last = null;
        for ($i = 0; $i < 6; $i++) {
            $last = $this->postJson('/api/v1/auth/login', [
                'email' => 'throttle-probe@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $last->assertStatus(429);
    }

    /* ------------------------------------------------------------------ *
     * Queue, storage, and CMS infrastructure
     * ------------------------------------------------------------------ */

    public function test_database_queue_and_failed_jobs_remain_compatible(): void
    {
        Phase14ProbeJob::dispatch()->onConnection('database');

        $this->assertSame(1, DB::table('jobs')->count());

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1, '--sleep' => 0])
            ->assertSuccessful();

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_public_disk_supports_cms_upload_round_trip(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('cms/1/probe.txt', 'probe');
        $this->assertTrue(Storage::disk('public')->exists('cms/1/probe.txt'));
        $this->assertSame('probe', Storage::disk('public')->get('cms/1/probe.txt'));
        Storage::disk('public')->delete('cms/1/probe.txt');
        $this->assertFalse(Storage::disk('public')->exists('cms/1/probe.txt'));
    }

    public function test_uploaded_cms_image_resolves_to_public_url(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => UploadedFile::fake()->createWithContent(
                    'hero.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
                ),
            ])
            ->assertOk();

        $this->assertStringStartsWith('/storage/', $response->json('data.image_url'));
    }
}
