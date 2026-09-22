<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\CmsContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase13CmsBannerTest extends TestCase
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

    /** @return array<string, mixed> */
    private function contentPayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'test-'.fake()->unique()->slug(),
            'type' => 'promo',
            'title' => 'Test Promo',
            'subtitle' => 'Limited time',
            'body' => 'Save big this weekend.',
            'cta_label' => 'Shop Now',
            'cta_url' => '/shop?category=men',
            'status' => 'draft',
            'sort_order' => 0,
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function bannerPayload(array $overrides = []): array
    {
        return array_merge([
            'key' => 'test-'.fake()->unique()->slug(),
            'title' => 'Test Slide',
            'image_path' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80',
            'status' => 'draft',
            'sort_order' => 0,
        ], $overrides);
    }

    /**
     * Minimal valid 1x1 PNG (magic bytes intact so MIME sniffing passes
     * without the GD extension). Repeated to reach a target byte size.
     */
    private function fakeImage(string $name = 'hero.png', int $repeat = 1): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        return UploadedFile::fake()->createWithContent($name, str_repeat($png, $repeat));
    }

    /* ------------------------------------------------------------------ *
     * CMS admin CRUD
     * ------------------------------------------------------------------ */

    public function test_admin_can_create_content(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload())
            ->assertCreated();

        $response->assertJsonPath('data.key', $response->json('data.key'));
        $this->assertDatabaseHas('cms_contents', ['key' => $response->json('data.key'), 'status' => 'draft']);
    }

    public function test_customer_cannot_create_content(): void
    {
        $this->actingAs($this->customer(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload())
            ->assertForbidden();
    }

    public function test_guest_cannot_create_content(): void
    {
        $this->postJson('/api/v1/admin/cms/content', $this->contentPayload())
            ->assertUnauthorized();
    }

    public function test_admin_can_update_content(): void
    {
        $admin = $this->admin();
        $content = CmsContent::factory()->create(['key' => 'updatable-block']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/cms/content/{$content->id}", ['title' => 'Updated Title', 'status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertSame('Updated Title', $content->fresh()->title);
    }

    public function test_admin_can_publish_content(): void
    {
        $admin = $this->admin();
        $content = CmsContent::factory()->create(['status' => 'draft']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cms/content/{$content->id}", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_admin_can_archive_content(): void
    {
        $admin = $this->admin();
        $content = CmsContent::factory()->create(['status' => 'published']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cms/content/{$content->id}", ['status' => 'archived'])
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_admin_can_delete_content(): void
    {
        $admin = $this->admin();
        $content = CmsContent::factory()->create(['key' => 'doomed-block']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/cms/content/{$content->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cms_contents', ['id' => $content->id]);
    }

    public function test_duplicate_content_key_rejected(): void
    {
        $admin = $this->admin();
        CmsContent::factory()->create(['key' => 'unique-block']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload(['key' => 'unique-block']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('key');
    }

    public function test_invalid_content_type_rejected(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload(['type' => 'blog']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_invalid_content_status_rejected(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload(['status' => 'live']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_reversed_schedule_rejected(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload([
                'starts_at' => now()->addDay()->toISOString(),
                'ends_at' => now()->subDay()->toISOString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_end_date_without_start_date_allowed(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload([
                'ends_at' => now()->addDay()->toISOString(),
            ]))
            ->assertCreated();
    }

    /* ------------------------------------------------------------------ *
     * CMS public visibility
     * ------------------------------------------------------------------ */

    public function test_future_content_hidden_from_public_api(): void
    {
        CmsContent::factory()->create([
            'key' => 'future-block',
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'ends_at' => null,
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=future-block')->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_expired_content_hidden_from_public_api(): void
    {
        CmsContent::factory()->create([
            'key' => 'expired-block',
            'status' => 'published',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=expired-block')->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_draft_content_hidden_from_public_api(): void
    {
        CmsContent::factory()->create(['key' => 'draft-block', 'status' => 'draft']);

        $response = $this->getJson('/api/v1/cms/content?key=draft-block')->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_archived_content_hidden_from_public_api(): void
    {
        CmsContent::factory()->create(['key' => 'archived-block', 'status' => 'archived']);

        $response = $this->getJson('/api/v1/cms/content?key=archived-block')->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_published_active_content_returned_in_order(): void
    {
        CmsContent::factory()->create(['key' => 'second-block', 'status' => 'published', 'sort_order' => 2]);
        CmsContent::factory()->create(['key' => 'first-block', 'status' => 'published', 'sort_order' => 1]);
        CmsContent::factory()->create(['key' => 'hidden-block', 'status' => 'draft', 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/cms/content')->assertOk();

        $keys = array_column($response->json('data'), 'key');
        $this->assertSame(['first-block', 'second-block'], $keys);
    }

    public function test_public_content_hides_admin_metadata(): void
    {
        CmsContent::factory()->create(['key' => 'visible-block', 'status' => 'published']);

        $response = $this->getJson('/api/v1/cms/content?key=visible-block')->assertOk();

        $response->assertJsonMissing(['created_by' => 1]);
        $this->assertArrayNotHasKey('created_by', $response->json('data.0'));
        $this->assertArrayNotHasKey('updated_by', $response->json('data.0'));
    }

    public function test_dangerous_cta_url_rejected(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,<h1>x</h1>', 'vbscript:msgbox(1)', 'file:///etc/passwd'] as $url) {
            $this->actingAs($this->admin(), 'sanctum')
                ->postJson('/api/v1/admin/cms/content', $this->contentPayload(['cta_url' => $url]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('cta_url');
        }
    }

    public function test_audit_event_created_for_content_mutations(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload(['key' => 'audited-block']))
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/cms/content/'.$created->json('data.id'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'cms.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'cms.deleted']);
    }

    /* ------------------------------------------------------------------ *
     * Banners
     * ------------------------------------------------------------------ */

    public function test_admin_can_create_banner(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/banners', $this->bannerPayload())
            ->assertCreated();

        $this->assertDatabaseHas('banners', ['key' => $response->json('data.key'), 'status' => 'draft']);
    }

    public function test_customer_cannot_create_banner(): void
    {
        $this->actingAs($this->customer(), 'sanctum')
            ->postJson('/api/v1/admin/banners', $this->bannerPayload())
            ->assertForbidden();
    }

    public function test_guest_cannot_create_banner(): void
    {
        $this->postJson('/api/v1/admin/banners', $this->bannerPayload())
            ->assertUnauthorized();
    }

    public function test_banner_requires_image(): void
    {
        $payload = $this->bannerPayload();
        unset($payload['image_path']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/banners', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image_path');
    }

    public function test_banner_image_upload_validation(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create(['status' => 'draft']);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_banner_ordering_is_deterministic(): void
    {
        Banner::factory()->create(['key' => 'slide-b', 'status' => 'published', 'sort_order' => 1]);
        Banner::factory()->create(['key' => 'slide-a', 'status' => 'published', 'sort_order' => 1]);
        Banner::factory()->create(['key' => 'slide-first', 'status' => 'published', 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/banners')->assertOk();
        $keys = array_column($response->json('data'), 'key');

        $this->assertSame('slide-first', $keys[0]);
        $this->assertCount(3, $keys);
    }

    public function test_scheduled_future_banner_hidden(): void
    {
        Banner::factory()->create([
            'key' => 'future-slide',
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $this->assertNotContains('future-slide', array_column($response->json('data'), 'key'));
    }

    public function test_expired_banner_hidden(): void
    {
        Banner::factory()->create([
            'key' => 'expired-slide',
            'status' => 'published',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $this->assertNotContains('expired-slide', array_column($response->json('data'), 'key'));
    }

    public function test_inactive_banner_hidden(): void
    {
        Banner::factory()->create(['key' => 'draft-slide', 'status' => 'draft']);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $this->assertNotContains('draft-slide', array_column($response->json('data'), 'key'));
    }

    public function test_public_banners_returns_only_active(): void
    {
        Banner::factory()->create(['key' => 'live-slide', 'status' => 'published', 'sort_order' => 0]);
        Banner::factory()->create(['key' => 'hidden-slide', 'status' => 'archived', 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/banners')->assertOk();
        $keys = array_column($response->json('data'), 'key');

        $this->assertSame(['live-slide'], $keys);
    }

    public function test_banner_sort_order_correct(): void
    {
        Banner::factory()->create(['key' => 'slide-3', 'status' => 'published', 'sort_order' => 30]);
        Banner::factory()->create(['key' => 'slide-1', 'status' => 'published', 'sort_order' => 10]);
        Banner::factory()->create(['key' => 'slide-2', 'status' => 'published', 'sort_order' => 20]);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $this->assertSame(
            ['slide-1', 'slide-2', 'slide-3'],
            array_column($response->json('data'), 'key')
        );
    }

    public function test_mobile_media_returned_correctly(): void
    {
        Banner::factory()->create([
            'key' => 'mobile-slide',
            'status' => 'published',
            'image_path' => 'https://example.com/desktop.jpg',
            'mobile_image_path' => 'https://example.com/mobile.jpg',
        ]);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $response->assertJsonPath('data.0.image_url', 'https://example.com/desktop.jpg');
        $response->assertJsonPath('data.0.mobile_image_url', 'https://example.com/mobile.jpg');
    }

    public function test_banner_dangerous_cta_rejected(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/banners', $this->bannerPayload(['cta_url' => 'javascript:alert(1)']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cta_url');
    }

    public function test_customer_cannot_modify_banner(): void
    {
        $banner = Banner::factory()->create(['status' => 'draft']);

        $this->actingAs($this->customer(), 'sanctum')
            ->patchJson("/api/v1/admin/banners/{$banner->id}", ['status' => 'published'])
            ->assertForbidden();

        $this->actingAs($this->customer(), 'sanctum')
            ->deleteJson("/api/v1/admin/banners/{$banner->id}")
            ->assertForbidden();
    }

    public function test_audit_event_created_for_banner_mutations(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/banners', $this->bannerPayload(['key' => 'audited-slide']))
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/banners/'.$created->json('data.id'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'banner.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'banner.deleted']);
    }

    /* ------------------------------------------------------------------ *
     * Storage
     * ------------------------------------------------------------------ */

    public function test_uploaded_image_stored_through_configured_filesystem(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage(),
            ])
            ->assertOk();

        $path = Banner::find($banner->id)->image_path;
        $this->assertStringStartsWith('banners/'.$banner->id.'/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('/storage/', $response->json('data.image_url'));
    }

    public function test_invalid_file_type_rejected(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $content = CmsContent::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/cms/content/{$content->id}/image", [
                'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_oversized_file_rejected(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage('huge.png', 100000),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_stored_paths_cannot_escape_storage_directory(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage('evil.png'),
            ])
            ->assertOk();

        $path = Banner::find($banner->id)->image_path;
        $this->assertStringNotContainsString('..', $path);
        $this->assertStringStartsWith('banners/'.$banner->id.'/', $path);
    }

    public function test_replaced_media_cleaned_up(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage('first.png'),
            ])
            ->assertOk();

        $firstPath = Banner::find($banner->id)->image_path;

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage('second.png'),
            ])
            ->assertOk();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists(Banner::find($banner->id)->image_path);
    }

    public function test_deleted_record_media_cleaned_up(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $banner = Banner::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/banners/{$banner->id}/image", [
                'image' => $this->fakeImage(),
            ])
            ->assertOk();

        $path = Banner::find($banner->id)->image_path;

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/banners/{$banner->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    /* ------------------------------------------------------------------ *
     * Security
     * ------------------------------------------------------------------ */

    public function test_customer_cannot_modify_admin_content(): void
    {
        $content = CmsContent::factory()->create(['key' => 'protected-block']);

        $this->actingAs($this->customer(), 'sanctum')
            ->patchJson("/api/v1/admin/cms/content/{$content->id}", ['title' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('protected-block', $content->fresh()->key);
    }

    public function test_html_body_stored_verbatim_for_text_rendering(): void
    {
        $admin = $this->admin();
        $payload = '<script>alert("xss")</script><p>Sale!</p>';

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/cms/content', $this->contentPayload([
                'key' => 'html-block',
                'status' => 'published',
                'body' => $payload,
            ]))
            ->assertCreated();

        // Stored verbatim (plain-text field); the storefront renders body
        // as text, never as HTML — no script execution surface.
        $this->assertSame($payload, $created->json('data.body'));

        $public = $this->getJson('/api/v1/cms/content?key=html-block')->assertOk();
        $this->assertSame($payload, $public->json('data.0.body'));
    }

    public function test_no_secrets_in_cms_api_responses(): void
    {
        CmsContent::factory()->create(['key' => 'clean-block', 'status' => 'published']);
        Banner::factory()->create(['key' => 'clean-slide', 'status' => 'published']);

        foreach (['/api/v1/cms/content', '/api/v1/banners'] as $endpoint) {
            $body = strtolower($this->getJson($endpoint)->assertOk()->getContent());

            foreach (['password', 'secret', 'checkout_token', 'remember_token', 'created_by', 'updated_by', 'card_'] as $needle) {
                $this->assertStringNotContainsString($needle, $body, "Leak in {$endpoint}: {$needle}");
            }
        }
    }
}
