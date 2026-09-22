<?php

namespace Tests\Feature;

use App\Jobs\DeliverMarketingEvent;
use App\Models\CancellationRequest;
use App\Models\Color;
use App\Models\MarketingAttribution;
use App\Models\MarketingEvent;
use App\Models\MarketingEventDelivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Size;
use App\Models\User;
use App\Services\Marketing\MarketingEventCatalog;
use App\Services\Marketing\MarketingProviderException;
use App\Services\Marketing\MarketingProviderResolver;
use App\Services\Marketing\MockMarketingProvider;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase12MarketingTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MockMarketingProvider::reset();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'price' => 50,
            'rating' => 0,
            'reviews_count' => 0,
            'stock_quantity' => 50,
            'in_stock' => true,
            'is_active' => true,
        ], $overrides));
    }

    private function grantConsent(User $user): void
    {
        $user->forceFill(['marketing_consent' => ['analytics' => true, 'marketing' => true]])->save();
    }

    /** @return array{order: Order, payment: Payment} */
    private function pendingOrderFor(?User $user = null, float $total = 100.00): array
    {
        $order = Order::factory()->create([
            'user_id' => $user?->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_provider' => 'demo',
            'currency' => 'USD',
            'subtotal' => $total,
            'shipping' => 0,
            'total' => $total,
        ]);
        $payment = $order->payment()->create([
            'provider' => 'demo',
            'status' => 'pending',
            'reference' => 'demo_'.strtolower(fake()->bothify('????####')),
            'amount' => $total,
            'amount_minor' => (int) ($total * 100),
            'currency' => 'USD',
        ]);

        return ['order' => $order->fresh(['user', 'items']), 'payment' => $payment];
    }

    private function addItem(Order $order, Product $product, int $quantity = 1): void
    {
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $variant = $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'MKT-M-INK-'.Str::random(6),
            'price' => (float) $product->price,
            'stock_quantity' => 50,
            'is_active' => true,
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
            'unit_price' => (float) $product->price,
            'line_total' => (float) $product->price * $quantity,
        ]);
    }

    /** @return array<string, mixed> */
    private function clientEvent(string $name, array $overrides = []): array
    {
        return array_merge([
            'event_id' => (string) Str::uuid(),
            'event_name' => $name,
            'anonymous_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
            'consent' => ['analytics' => true, 'marketing' => true],
            'metadata' => [],
        ], $overrides);
    }

    /* ------------------------------------------------------------------ *
     * Event catalog
     * ------------------------------------------------------------------ */

    public function test_catalog_lists_supported_events(): void
    {
        $names = MarketingEventCatalog::names();

        foreach (['page_view', 'product_view', 'search', 'add_to_cart', 'remove_from_cart', 'view_cart', 'begin_checkout', 'purchase', 'payment_failed', 'order_cancelled', 'refund_completed'] as $expected) {
            $this->assertContains($expected, $names);
        }

        $this->assertSame(11, count($names));
        $this->assertTrue(MarketingEventCatalog::isServerOnly('purchase'));
        $this->assertFalse(MarketingEventCatalog::isServerOnly('add_to_cart'));
    }

    public function test_unsupported_event_rejected(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('checkout_completed'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event_name');
    }

    public function test_server_only_events_rejected_from_ingestion(): void
    {
        foreach (['purchase', 'payment_failed', 'order_cancelled', 'refund_completed'] as $name) {
            $this->postJson('/api/v1/marketing/events', $this->clientEvent($name))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('event_name');
        }

        $this->assertSame(0, MarketingEvent::query()->count());
    }

    /* ------------------------------------------------------------------ *
     * Ingestion
     * ------------------------------------------------------------------ */

    public function test_anonymous_event_accepted(): void
    {
        $product = $this->product();

        $response = $this->postJson('/api/v1/marketing/events', $this->clientEvent('product_view', [
            'product_external_id' => $product->external_id,
            'metadata' => ['slug' => $product->slug, 'category' => 'test', 'currency' => 'usd'],
        ]))->assertCreated();

        $response->assertJsonPath('data.event_name', 'product_view');
        $response->assertJsonPath('data.accepted', true);

        $this->assertDatabaseHas('marketing_events', [
            'event_name' => 'product_view',
            'event_source' => 'client',
            'user_id' => null,
            'product_external_id' => $product->external_id,
        ]);
    }

    public function test_authenticated_event_uses_sanctum_identity(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/events', array_merge(
                $this->clientEvent('add_to_cart', [
                    'product_external_id' => $product->external_id,
                    'metadata' => ['quantity' => 2],
                ]),
                ['user_id' => 999999, 'user' => ['id' => 999999]],
            ))
            ->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame($customer->id, $event->user_id);
    }

    public function test_malformed_payload_rejected(): void
    {
        $this->postJson('/api/v1/marketing/events', ['anonymous_id' => (string) Str::uuid()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event_name');

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('search', ['metadata' => 'not-an-array']))
            ->assertUnprocessable();
    }

    public function test_metadata_values_are_trimmed_to_limits(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('search', [
            'metadata' => ['query' => str_repeat('a', 5000), 'result_count' => 5],
        ]))->assertCreated();

        $metadata = MarketingEvent::query()->firstOrFail()->metadata;
        $this->assertSame(200, mb_strlen($metadata['query']));
        $this->assertSame(5, $metadata['result_count']);
    }

    public function test_duplicate_event_id_is_idempotent(): void
    {
        $eventId = (string) Str::uuid();
        $payload = $this->clientEvent('view_cart', ['event_id' => $eventId, 'metadata' => ['item_count' => 2]]);

        $this->postJson('/api/v1/marketing/events', $payload)->assertCreated();
        $this->postJson('/api/v1/marketing/events', $payload)->assertOk();

        $this->assertSame(1, MarketingEvent::query()->where('event_id', $eventId)->count());
    }

    public function test_duplicate_event_id_from_another_identity_is_hidden(): void
    {
        $eventId = (string) Str::uuid();
        $first = $this->customer();
        $second = $this->customer();

        $this->actingAs($first, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('view_cart', ['event_id' => $eventId]))
            ->assertCreated();

        // Same event_id re-presented by a different identity looks like 404.
        $this->actingAs($second, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('view_cart', ['event_id' => $eventId]))
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Attribution
     * ------------------------------------------------------------------ */

    public function test_utm_and_click_ids_captured(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'metadata' => ['path' => '/shop'],
            'attribution' => [
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'welcome',
                'utm_term' => 'shoes',
                'gclid' => 'abc123',
                'fbclid' => 'fb-456',
                'landing_url' => 'http://localhost:5173/shop?utm_source=newsletter',
                'referrer' => 'https://example.com/',
            ],
        ]))->assertCreated();

        $attribution = MarketingAttribution::query()->firstOrFail();
        $this->assertSame('newsletter', $attribution->source);
        $this->assertSame('email', $attribution->medium);
        $this->assertSame('welcome', $attribution->campaign);
        $this->assertSame('shoes', $attribution->term);
        $this->assertSame(['gclid' => 'abc123', 'fbclid' => 'fb-456'], $attribution->click_ids);
        $this->assertSame('newsletter', $attribution->first_source);
        $this->assertNotNull($attribution->first_seen_at);
        $this->assertNotNull($attribution->last_seen_at);

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame($attribution->id, $event->attribution_id);
    }

    public function test_unapproved_attribution_params_discarded(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'attribution' => [
                'utm_source' => 'newsletter',
                'password' => 'hunter2',
                'checkout_token' => 'secret-token',
                'auth_token' => 'bearer-xyz',
                'gclid' => 'not valid!!',
                'landing_url' => 'https://user:pass@evil.example/x',
            ],
        ]))->assertCreated();

        $attribution = MarketingAttribution::query()->firstOrFail();
        $blob = strtolower(json_encode($attribution->toArray()));

        foreach (['hunter2', 'secret-token', 'bearer-xyz', 'not valid', 'user:pass'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob, "Leak: {$needle}");
        }

        $this->assertSame('newsletter', $attribution->source);
        $this->assertSame([], $attribution->click_ids ?? []);
    }

    public function test_first_touch_retained_last_touch_updated(): void
    {
        $anonymousId = (string) Str::uuid();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'anonymous_id' => $anonymousId,
            'attribution' => ['utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'welcome'],
        ]))->assertCreated();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'anonymous_id' => $anonymousId,
            'attribution' => ['utm_source' => 'ads', 'utm_medium' => 'cpc', 'utm_campaign' => 'summer'],
        ]))->assertCreated();

        $this->assertSame(1, MarketingAttribution::query()->count());
        $row = MarketingAttribution::query()->firstOrFail();
        $this->assertSame('newsletter', $row->first_source);
        $this->assertSame('email', $row->first_medium);
        $this->assertSame('welcome', $row->first_campaign);
        $this->assertSame('ads', $row->source);
        $this->assertSame('cpc', $row->medium);
        $this->assertSame('summer', $row->campaign);
        $this->assertTrue($row->first_seen_at->lessThanOrEqualTo($row->last_seen_at));
    }

    public function test_anonymous_attribution_survives_login_without_merging(): void
    {
        $anonymousId = (string) Str::uuid();
        $first = $this->customer();
        $second = $this->customer();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'anonymous_id' => $anonymousId,
            'attribution' => ['utm_source' => 'newsletter'],
        ]))->assertCreated();

        $this->actingAs($first, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', ['anonymous_id' => $anonymousId]))
            ->assertCreated();

        $this->assertSame($first->id, MarketingAttribution::query()->firstOrFail()->user_id);

        // A different user presenting the same anonymous id must not steal it.
        $this->actingAs($second, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', ['anonymous_id' => $anonymousId]))
            ->assertCreated();

        $this->assertSame(1, MarketingAttribution::query()->count());
        $this->assertSame($first->id, MarketingAttribution::query()->firstOrFail()->user_id);
    }

    public function test_direct_traffic_creates_no_attribution_row(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'metadata' => ['path' => '/'],
        ]))->assertCreated();

        $this->assertSame(0, MarketingAttribution::query()->count());
        $this->assertNull(MarketingEvent::query()->firstOrFail()->attribution_id);
    }

    /* ------------------------------------------------------------------ *
     * Consent
     * ------------------------------------------------------------------ */

    public function test_consent_denied_declines_storage(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('product_view', [
            'consent' => ['analytics' => false, 'marketing' => false],
        ]))->assertAccepted()->assertJsonPath('data.accepted', false);

        $this->assertSame(0, MarketingEvent::query()->count());
    }

    public function test_unknown_consent_defaults_to_denied(): void
    {
        $this->postJson('/api/v1/marketing/events', [
            'event_id' => (string) Str::uuid(),
            'event_name' => 'product_view',
            'anonymous_id' => (string) Str::uuid(),
        ])->assertAccepted();

        $this->assertSame(0, MarketingEvent::query()->count());
    }

    public function test_stored_user_consent_wins_over_snapshot(): void
    {
        $customer = $this->customer();
        $this->grantConsent($customer);

        // Stored grant applies even without a snapshot.
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/events', [
                'event_id' => (string) Str::uuid(),
                'event_name' => 'view_cart',
                'metadata' => ['item_count' => 1],
            ])
            ->assertCreated();

        // Stored denial wins over a granted snapshot.
        $customer->forceFill(['marketing_consent' => ['analytics' => false, 'marketing' => false]])->save();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('view_cart'))
            ->assertAccepted();

        $this->assertSame(1, MarketingEvent::query()->count());
    }

    public function test_consent_preferences_round_trip(): void
    {
        $customer = $this->customer();

        // Unauthenticated first: actingAs persists for later requests.
        $this->getJson('/api/v1/profile/marketing-consent')->assertUnauthorized();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/profile/marketing-consent')
            ->assertOk()
            ->assertJsonPath('data.analytics', false)
            ->assertJsonPath('data.marketing', false);

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/profile/marketing-consent', ['analytics' => true, 'marketing' => false])
            ->assertOk()
            ->assertJsonPath('data.analytics', true)
            ->assertJsonPath('data.marketing', false);
    }

    /* ------------------------------------------------------------------ *
     * Client events
     * ------------------------------------------------------------------ */

    public function test_product_view_event(): void
    {
        $product = $this->product();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('product_view', [
            'product_external_id' => $product->external_id,
            'metadata' => [
                'slug' => $product->slug,
                'category' => 'test',
                'currency' => 'usd',
                'price' => 999,
                'internal_notes' => 'drop me',
            ],
        ]))->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame($product->external_id, $event->product_external_id);
        $this->assertSame([
            'slug' => $product->slug,
            'category' => 'test',
            'currency' => 'USD',
        ], $event->metadata);
    }

    public function test_unknown_product_rejected(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('product_view', [
            'product_external_id' => 'no-such-product',
        ]))->assertUnprocessable();
    }

    public function test_search_event(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('search', [
            'metadata' => ['query' => '  linen shirt  ', 'result_count' => '12', 'page' => 3],
        ]))->assertCreated();

        $this->assertSame(
            ['query' => 'linen shirt', 'result_count' => 12],
            MarketingEvent::query()->firstOrFail()->metadata
        );
    }

    public function test_add_to_cart_event(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/events', $this->clientEvent('add_to_cart', [
                'product_external_id' => $product->external_id,
                'metadata' => ['quantity' => 2, 'unit_price' => 50],
            ]))
            ->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame($customer->id, $event->user_id);
        $this->assertSame(['quantity' => 2], $event->metadata);
    }

    public function test_remove_from_cart_and_view_cart_events(): void
    {
        $product = $this->product();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('remove_from_cart', [
            'product_external_id' => $product->external_id,
            'metadata' => ['quantity' => 1],
        ]))->assertCreated();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('view_cart', [
            'metadata' => ['item_count' => 3],
        ]))->assertCreated();

        $this->assertSame(2, MarketingEvent::query()->count());
    }

    public function test_begin_checkout_event(): void
    {
        $this->postJson('/api/v1/marketing/events', $this->clientEvent('begin_checkout', [
            'currency' => 'usd',
            'value' => '189.95',
            'metadata' => ['item_count' => 2],
        ]))->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $this->assertSame('USD', $event->currency);
        $this->assertEquals(189.95, $event->value);
    }

    /* ------------------------------------------------------------------ *
     * Purchase conversion
     * ------------------------------------------------------------------ */

    public function test_purchase_generated_from_paid_lifecycle(): void
    {
        $customer = $this->customer();
        $this->grantConsent($customer);
        $product = $this->product(['price' => 60]);
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer, 120.00);
        $this->addItem($order, $product, 2);

        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        $event = MarketingEvent::query()->where('event_name', 'purchase')->firstOrFail();
        $this->assertSame("purchase:{$order->id}", $event->event_id);
        $this->assertSame('server', $event->event_source);
        $this->assertSame($customer->id, $event->user_id);
        $this->assertSame($order->id, $event->order_id);
        $this->assertSame($order->number, $event->order_number);
        $this->assertSame('USD', $event->currency);
        $this->assertEquals(120.00, $event->value);
        $this->assertSame('necessary', $event->consent_state);

        $lines = $event->metadata['lines'];
        $this->assertCount(1, $lines);
        $this->assertSame($product->external_id, $lines[0]['product_external_id']);
        $this->assertSame(2, $lines[0]['quantity']);
    }

    public function test_duplicate_mark_paid_does_not_duplicate_purchase(): void
    {
        $customer = $this->customer();
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);

        $service = app(OrderFulfillmentService::class);
        $service->markPaid($order->fresh(), $payment->fresh());
        $service->markPaid($order->fresh(), $payment->fresh());

        $this->assertSame(1, MarketingEvent::query()->where('event_name', 'purchase')->count());
    }

    public function test_order_confirmation_revisit_creates_nothing(): void
    {
        $customer = $this->customer();
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);
        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->number}")
            ->assertOk();

        $this->assertSame(1, MarketingEvent::query()->count());
    }

    /* ------------------------------------------------------------------ *
     * Refund / cancellation conversions
     * ------------------------------------------------------------------ */

    public function test_refund_completed_event_on_succeeded_refund(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
            'currency' => 'USD',
            'subtotal' => 80,
            'shipping' => 0,
            'total' => 80,
        ]);

        $refund = Refund::query()->create([
            'order_id' => $order->id,
            'requested_by' => $customer->id,
            'provider' => 'demo',
            'status' => 'pending',
            'amount' => 80,
            'currency' => 'USD',
            'reason' => 'return',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refund->id}", ['status' => 'succeeded'])
            ->assertOk();

        $event = MarketingEvent::query()->where('event_name', 'refund_completed')->firstOrFail();
        $this->assertSame("refund_completed:{$refund->id}", $event->event_id);
        $this->assertEquals(80, $event->value);

        // Re-finalizing is rejected and cannot duplicate the event.
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refund->id}", ['status' => 'succeeded'])
            ->assertUnprocessable();

        $this->assertSame(1, MarketingEvent::query()->where('event_name', 'refund_completed')->count());
    }

    public function test_order_cancelled_event_on_execution(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
            'currency' => 'USD',
            'subtotal' => 50,
            'shipping' => 0,
            'total' => 50,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Changed my mind.'])
            ->assertCreated();

        $cancellationId = CancellationRequest::query()->firstOrFail()->id;

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'approved'])
            ->assertOk();

        $event = MarketingEvent::query()->where('event_name', 'order_cancelled')->firstOrFail();
        $this->assertSame($order->number, $event->order_number);

        // Approving twice is rejected; the event stays singular.
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'approved'])
            ->assertUnprocessable();

        $this->assertSame(1, MarketingEvent::query()->where('event_name', 'order_cancelled')->count());
    }

    /* ------------------------------------------------------------------ *
     * Queue and provider delivery
     * ------------------------------------------------------------------ */

    public function test_provider_delivery_queued_for_consented_user(): void
    {
        Queue::fake();

        $customer = $this->customer();
        $this->grantConsent($customer);
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);

        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        Queue::assertPushed(DeliverMarketingEvent::class, 1);

        $event = MarketingEvent::query()->where('event_name', 'purchase')->firstOrFail();
        $this->assertSame(1, MarketingEventDelivery::query()->where('marketing_event_id', $event->id)->count());
        $this->assertSame('queued', MarketingEventDelivery::query()->firstOrFail()->status);
    }

    public function test_no_delivery_without_marketing_consent(): void
    {
        Queue::fake();

        $customer = $this->customer();
        $customer->forceFill(['marketing_consent' => ['analytics' => true, 'marketing' => false]])->save();
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);

        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        // Ledger row exists (necessary), but nothing fans out.
        $this->assertSame(1, MarketingEvent::query()->where('event_name', 'purchase')->count());
        Queue::assertNotPushed(DeliverMarketingEvent::class);
        $this->assertSame(0, MarketingEventDelivery::query()->count());
    }

    public function test_worker_retry_then_terminal_failure(): void
    {
        $event = MarketingEvent::factory()->create(['event_name' => 'purchase']);
        $delivery = MarketingEventDelivery::factory()->create([
            'marketing_event_id' => $event->id,
            'provider' => 'mock',
            'status' => 'queued',
        ]);

        MockMarketingProvider::$failOnce[] = $event->event_id;

        try {
            (new DeliverMarketingEvent($delivery->id))->handle(app(MarketingProviderResolver::class));
            $this->fail('Transient failure should throw.');
        } catch (MarketingProviderException $exception) {
            $this->assertTrue($exception->isRetryable());
        }

        $this->assertSame(1, $delivery->fresh()->attempts);
        $this->assertSame('queued', $delivery->fresh()->status);

        (new DeliverMarketingEvent($delivery->id))->handle(app(MarketingProviderResolver::class));

        $fresh = $delivery->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame(2, $fresh->attempts);
        $this->assertNotNull($fresh->sent_at);
        $this->assertCount(1, MockMarketingProvider::sent());
    }

    public function test_terminal_failure_marks_row_failed(): void
    {
        $event = MarketingEvent::factory()->create(['event_name' => 'purchase']);
        $delivery = MarketingEventDelivery::factory()->create([
            'marketing_event_id' => $event->id,
            'provider' => 'mock',
            'status' => 'queued',
        ]);

        MockMarketingProvider::$failAlways[] = $event->event_id;

        try {
            (new DeliverMarketingEvent($delivery->id))->handle(app(MarketingProviderResolver::class));
            $this->fail('Permanent failure should throw.');
        } catch (MarketingProviderException) {
            // Expected.
        }

        (new DeliverMarketingEvent($delivery->id))->failed(
            new MarketingProviderException('Mock rejected.', 'mock_rejected', false)
        );

        $fresh = $delivery->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame('mock_rejected', $fresh->error_code);
        $this->assertNotNull($fresh->failed_at);
    }

    /* ------------------------------------------------------------------ *
     * Provider behavior
     * ------------------------------------------------------------------ */

    public function test_mock_provider_sends_successfully(): void
    {
        $event = MarketingEvent::factory()->create(['event_name' => 'purchase']);
        $provider = app(MarketingProviderResolver::class)->all()[0];

        $this->assertSame('mock', $provider->name());
        $this->assertTrue($provider->supports($event));

        $result = $provider->send($event, ['event_id' => $event->event_id]);

        $this->assertSame('sent', $result['status']);
        $this->assertNotEmpty($result['provider_event_id']);
        $this->assertSame($event->event_id, MockMarketingProvider::sent()[0]['idempotency_key']);
    }

    public function test_unknown_provider_fails_safely(): void
    {
        config(['marketing.providers' => ['bogus']]);

        $customer = $this->customer();
        $this->grantConsent($customer);
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);

        // Checkout-independent: the paid transition still succeeds.
        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, MarketingEvent::query()->where('event_name', 'purchase')->count());
        $this->assertSame(0, MarketingEventDelivery::query()->count());
    }

    /* ------------------------------------------------------------------ *
     * Delivery idempotency
     * ------------------------------------------------------------------ */

    public function test_same_event_provider_not_delivered_twice(): void
    {
        $customer = $this->customer();
        $this->grantConsent($customer);
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);

        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        $delivery = MarketingEventDelivery::query()->firstOrFail();
        (new DeliverMarketingEvent($delivery->id))->handle(app(MarketingProviderResolver::class));

        $this->assertCount(1, MockMarketingProvider::sent());
    }

    public function test_provider_idempotency_key_is_stable(): void
    {
        $event = MarketingEvent::factory()->create(['event_name' => 'purchase']);
        $delivery = MarketingEventDelivery::factory()->create([
            'marketing_event_id' => $event->id,
            'provider' => 'mock',
        ]);

        (new DeliverMarketingEvent($delivery->id))->handle(app(MarketingProviderResolver::class));

        $this->assertSame($event->event_id, MockMarketingProvider::sent()[0]['idempotency_key']);
    }

    /* ------------------------------------------------------------------ *
     * Security
     * ------------------------------------------------------------------ */

    public function test_no_arbitrary_send_endpoints_exist(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/marketing/send', ['event_name' => 'purchase'])
            ->assertNotFound();
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/providers/dispatch', ['event_name' => 'purchase'])
            ->assertNotFound();
    }

    public function test_stored_payloads_hold_no_sensitive_data(): void
    {
        $product = $this->product();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('product_view', [
            'product_external_id' => $product->external_id,
            'metadata' => [
                'slug' => $product->slug,
                'password' => 'hunter2',
                'checkout_token' => 'secret',
                'card_number' => '4242424242424242',
            ],
        ]))->assertCreated();

        $event = MarketingEvent::query()->firstOrFail();
        $blob = strtolower(json_encode($event->toArray()));

        foreach (['hunter2', 'secret', '42424242', 'password', 'checkout_token', 'card_number', 'remember_token', 'authorization'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob, "Sensitive leak: {$needle}");
        }

        $this->assertSame(['slug'], array_keys($event->metadata));
    }

    public function test_rate_limiting_applies_to_ingestion(): void
    {
        $last = null;
        for ($i = 0; $i < 61; $i++) {
            $last = $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
                'metadata' => ['path' => '/'],
            ]));
        }

        $last->assertStatus(429);
    }

    /* ------------------------------------------------------------------ *
     * Retention
     * ------------------------------------------------------------------ */

    public function test_old_events_pruned_recent_kept(): void
    {
        MarketingEvent::factory()->create(['occurred_at' => now()->subDays(100)]);
        $recent = MarketingEvent::factory()->create(['occurred_at' => now()->subDays(5)]);
        MarketingAttribution::factory()->create(['last_seen_at' => now()->subDays(100)]);

        $this->artisan('marketing:prune')->assertSuccessful();

        $this->assertSame(1, MarketingEvent::query()->count());
        $this->assertTrue($recent->fresh()->exists());
        $this->assertSame(0, MarketingAttribution::query()->count());
    }

    public function test_pruning_is_idempotent(): void
    {
        $this->artisan('marketing:prune')->assertSuccessful();
        $this->artisan('marketing:prune')->assertSuccessful();
    }

    public function test_events_disabled_kill_switch(): void
    {
        config(['marketing.events_enabled' => false]);

        $customer = $this->customer();

        $this->postJson('/api/v1/marketing/events', $this->clientEvent('page_view', [
            'metadata' => ['path' => '/'],
        ]))->assertServiceUnavailable();

        ['order' => $order, 'payment' => $payment] = $this->pendingOrderFor($customer);
        app(OrderFulfillmentService::class)->markPaid($order->fresh(), $payment->fresh());

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(0, MarketingEvent::query()->count());
    }
}
