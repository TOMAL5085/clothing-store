<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\RefundCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlwaysFailsReliabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new \RuntimeException('Simulated job failure.');
    }
}

class Phase9ReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    /* ------------------------------------------------------------------ *
     * Health
     * ------------------------------------------------------------------ */

    public function test_health_endpoint_reports_healthy(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Request-ID');

        $body = strtolower($response->getContent());
        foreach (['password', 'secret', 'dsn', 'pgsql:'] as $needle) {
            $this->assertStringNotContainsString($needle, $body);
        }
    }

    public function test_health_endpoint_reports_unhealthy_when_database_is_down(): void
    {
        // Production error shape: the /up route only masks failures with
        // debug disabled (local .env.testing enables it).
        config(['app.debug' => false]);
        config(['database.connections.pgsql.host' => 'invalid-host-xyz']);
        DB::purge('pgsql');

        $this->get('/up')->assertServerError();

        // Restore connectivity so the RefreshDatabase rollback can complete.
        config(['database.connections.pgsql.host' => '127.0.0.1']);
        DB::purge('pgsql');
    }

    /* ------------------------------------------------------------------ *
     * Request ID
     * ------------------------------------------------------------------ */

    public function test_request_id_header_is_returned_and_unique(): void
    {
        $first = $this->getJson('/api/v1/products')->assertOk();
        $second = $this->getJson('/api/v1/products')->assertOk();

        $firstId = $first->headers->get('X-Request-ID');
        $secondId = $second->headers->get('X-Request-ID');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $firstId
        );
        $this->assertNotSame($firstId, $secondId);
    }

    public function test_request_id_grants_no_authorization(): void
    {
        $this->getJson('/api/v1/admin/orders', ['X-Request-ID' => '123e4567-e89b-12d3-a456-426614174000'])
            ->assertUnauthorized();
    }

    /* ------------------------------------------------------------------ *
     * Error contract
     * ------------------------------------------------------------------ */

    public function test_unexpected_failure_returns_safe_shape_with_request_id(): void
    {
        // Production error shape: local .env.testing enables debug mode.
        config(['app.debug' => false]);
        config(['database.connections.pgsql.host' => 'invalid-host-xyz']);
        DB::purge('pgsql');

        $response = $this->getJson('/api/v1/products');

        $response->assertServerError();
        $response->assertJsonPath('message', 'Server error.');
        $response->assertJsonPath('code', 'INTERNAL_ERROR');
        $response->assertHeader('X-Request-ID');
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('request_id')
        );

        $body = strtolower($response->getContent());
        foreach (['trace', 'storage/', '.php', 'app_key', 'secret', 'password'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, "Leak: {$needle}");
        }

        // Restore connectivity so the RefreshDatabase rollback can complete.
        config(['database.connections.pgsql.host' => '127.0.0.1']);
        DB::purge('pgsql');
    }

    public function test_validation_errors_keep_their_shape(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /* ------------------------------------------------------------------ *
     * Queue and failed jobs
     * ------------------------------------------------------------------ */

    public function test_failing_job_is_persisted_to_failed_jobs(): void
    {
        AlwaysFailsReliabilityJob::dispatch()->onConnection('database');

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1, '--sleep' => 0])
            ->assertSuccessful();

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());

        $failed = DB::table('failed_jobs')->first();
        $this->assertStringContainsString('Simulated job failure.', $failed->exception);
        $this->assertStringNotContainsString('password', strtolower($failed->payload.$failed->exception));
    }

    public function test_failed_job_is_retried_before_failing(): void
    {
        AlwaysFailsReliabilityJob::dispatch()->onConnection('database');

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 2, '--sleep' => 0])
            ->assertSuccessful();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 2, '--sleep' => 0])
            ->assertSuccessful();
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_failed_jobs_can_be_listed_and_retried(): void
    {
        AlwaysFailsReliabilityJob::dispatch()->onConnection('database');
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1, '--sleep' => 0]);

        $this->artisan('queue:failed')
            ->assertSuccessful()
            ->expectsOutputToContain('database');
    }

    /* ------------------------------------------------------------------ *
     * Scheduler and maintenance
     * ------------------------------------------------------------------ */

    public function test_maintenance_tasks_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => (string) ($event->command ?? ''))
            ->all();

        $this->assertTrue(collect($commands)->contains(fn ($command) => str_contains($command, 'audit:prune')));
        $this->assertTrue(collect($commands)->contains(fn ($command) => str_contains($command, 'queue:prune-failed')));
    }

    public function test_audit_prune_is_idempotent(): void
    {
        config(['security.audit_retention_days' => 30]);
        AuditLog::factory()->create(['created_at' => now()->subDays(60)]);

        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertSame(0, AuditLog::query()->count());
        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_app_check_passes_without_leaking_secrets(): void
    {
        $this->artisan('app:check')->assertSuccessful();

        $output = strtolower(\Artisan::output());
        foreach (['test_password', 'whsec_test', 'sk_test', 'secret='] as $needle) {
            $this->assertStringNotContainsString($needle, $output, "Leak: {$needle}");
        }
    }

    public function test_app_check_fails_on_invalid_queue_config(): void
    {
        config(['queue.default' => 'bogus-driver']);

        $this->artisan('app:check')->assertFailed();
    }

    /* ------------------------------------------------------------------ *
     * Pagination limits
     * ------------------------------------------------------------------ */

    public function test_notification_listing_clamps_page_size(): void
    {
        $customer = $this->customer();
        $order = Order::factory()->create(['user_id' => $customer->id]);
        $customer->notify(new OrderPlacedNotification($order));

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/notifications?per_page=5000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_review_listing_clamps_page_size(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        Review::factory()->approved()->create([
            'user_id' => $this->customer()->id,
            'product_id' => $product->id,
        ]);

        $this->getJson("/api/v1/products/{$product->slug}/reviews?per_page=5000")
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_admin_listing_clamps_page_size(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/returns?per_page=5000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    /* ------------------------------------------------------------------ *
     * Query-count regression guard
     * ------------------------------------------------------------------ */

    public function test_product_listing_query_count_is_bounded(): void
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->getJson('/api/v1/products?per_page=5')->assertOk();

        $this->assertLessThanOrEqual(20, $queries);
    }

    /* ------------------------------------------------------------------ *
     * Inventory and resolution idempotency
     * ------------------------------------------------------------------ */

    private function cancellableOrderFor(User $user): Order
    {
        return Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'total' => 100,
            'subtotal' => 100,
        ]);
    }

    public function test_duplicate_cancellation_review_is_rejected(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->cancellableOrderFor($customer);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Changed my mind.'])
            ->assertCreated();

        $id = CancellationRequest::query()->firstOrFail()->id;

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$id}", ['decision' => 'approved'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$id}", ['decision' => 'approved'])
            ->assertUnprocessable();
    }

    public function test_duplicate_return_received_is_rejected(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $product = Product::factory()->create(['price' => 60, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total' => 60,
            'subtotal' => 60,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'quantity' => 1,
            'unit_price' => 60,
            'line_total' => 60,
        ]);

        $created = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/returns", [
                'reason' => 'Damaged.',
                'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
            ])->assertOk();

        $returnId = $created->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/returns/{$returnId}", ['decision' => 'approved'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/returns/{$returnId}/received", [])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/returns/{$returnId}/received", [])
            ->assertUnprocessable();

        $this->assertSame(1, Refund::query()->where('return_request_id', $returnId)->count());
    }

    public function test_repeated_refund_update_does_not_duplicate_completion(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->cancellableOrderFor($customer);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->number}/cancellation", ['reason' => 'Changed my mind.'])
            ->assertCreated();

        $cancellationId = CancellationRequest::query()->firstOrFail()->id;
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/cancellations/{$cancellationId}", ['decision' => 'approved'])
            ->assertOk();

        $refundId = Refund::query()->firstOrFail()->id;

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", ['status' => 'succeeded'])
            ->assertOk();
        // Re-finalizing is rejected outright, so no second completion exists.
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/refunds/{$refundId}", ['status' => 'succeeded'])
            ->assertUnprocessable();

        Notification::assertSentTimes(
            RefundCompletedNotification::class, 1
        );
    }

    /* ------------------------------------------------------------------ *
     * Phase 8 regression subset
     * ------------------------------------------------------------------ */

    public function test_login_throttle_still_enforced(): void
    {
        $this->customer();

        $last = null;
        for ($i = 0; $i < 6; $i++) {
            $last = $this->postJson('/api/v1/auth/login', [
                'email' => 'victim@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $last->assertStatus(429);
    }

    public function test_admin_mutation_still_writes_audit_log(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['status' => 'processing', 'payment_status' => 'paid', 'total' => 10, 'subtotal' => 10]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/orders/{$order->number}/status", ['status' => 'shipped'])
            ->assertOk();

        $this->assertSame(1, AuditLog::query()->where('action', 'order.status_updated')->count());
    }

    public function test_customer_still_cannot_access_admin(): void
    {
        $this->actingAs($this->customer(), 'sanctum')
            ->getJson('/api/v1/admin/orders')
            ->assertForbidden();
    }
}
