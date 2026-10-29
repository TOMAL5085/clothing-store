<?php

namespace Tests\Feature;

use App\Models\CmsContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseACmsStaticPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_cms_content_supports_static_page_types(): void
    {
        $this->assertContains('about_us', CmsContent::TYPES);
        $this->assertContains('contact_us', CmsContent::TYPES);
        $this->assertContains('terms_conditions', CmsContent::TYPES);
        $this->assertContains('faq', CmsContent::TYPES);
        $this->assertContains('help_center', CmsContent::TYPES);
    }

    public function test_public_cms_can_fetch_about_us_by_key(): void
    {
        CmsContent::factory()->create([
            'key' => 'about-us',
            'type' => 'about_us',
            'title' => 'About Us',
            'body' => 'We sell great clothes.',
            'status' => 'published',
        ]);
        CmsContent::factory()->create([
            'key' => 'draft-page',
            'type' => 'about_us',
            'title' => 'Draft',
            'body' => 'Not visible yet.',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=about-us')->assertOk();

        $keys = array_column($response->json('data'), 'key');
        $this->assertContains('about-us', $keys);
        $this->assertNotContains('draft-page', $keys);
    }

    public function test_public_cms_can_fetch_contact_us_by_key(): void
    {
        CmsContent::factory()->create([
            'key' => 'contact-us',
            'type' => 'contact_us',
            'title' => 'Contact Us',
            'body' => 'Email us at hello@example.com.',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=contact-us')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('contact-us', $response->json('data.0.key'));
        $this->assertSame('Contact Us', $response->json('data.0.title'));
    }

    public function test_public_cms_can_fetch_terms_and_conditions_by_key(): void
    {
        CmsContent::factory()->create([
            'key' => 'terms-conditions',
            'type' => 'terms_conditions',
            'title' => 'Terms & Conditions',
            'body' => 'By using this site you agree to our terms.',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=terms-conditions')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('terms-conditions', $response->json('data.0.key'));
    }

    public function test_public_cms_can_fetch_faq_by_key(): void
    {
        CmsContent::factory()->create([
            'key' => 'faqs',
            'type' => 'faq',
            'title' => 'FAQs',
            'body' => 'Frequently asked questions.',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=faqs')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('faqs', $response->json('data.0.key'));
    }

    public function test_public_cms_can_fetch_help_center_by_key(): void
    {
        CmsContent::factory()->create([
            'key' => 'help-center',
            'type' => 'help_center',
            'title' => 'Help Center',
            'body' => 'Get help with your order.',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cms/content?key=help-center')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('help-center', $response->json('data.0.key'));
    }

    public function test_admin_can_create_static_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/cms/content', [
                'key' => 'about-us',
                'type' => 'about_us',
                'title' => 'About Us',
                'body' => 'Our story.',
                'status' => 'published',
            ])->assertCreated()
            ->assertJsonPath('data.key', 'about-us')
            ->assertJsonPath('data.type', 'about_us');

        $this->assertDatabaseHas('cms_contents', ['key' => 'about-us', 'type' => 'about_us']);
    }

    public function test_draft_static_page_is_hidden_from_public(): void
    {
        CmsContent::factory()->create([
            'key' => 'draft-about',
            'type' => 'about_us',
            'title' => 'Draft About',
            'body' => 'Not published.',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/cms/content?key=draft-about')->assertOk()->assertJsonCount(0, 'data');
    }
}
