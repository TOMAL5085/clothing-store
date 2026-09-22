<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * First-party marketing measurement foundation. The events table is the
     * ledger of record; attributions link anonymous touchpoints to users;
     * deliveries track per-provider fan-out. No vendor-specific columns.
     */
    public function up(): void
    {
        Schema::create('marketing_attributions', function (Blueprint $table) {
            $table->id();
            $table->string('anonymous_id', 64)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->string('source', 120)->nullable();
            $table->string('medium', 120)->nullable();
            $table->string('campaign', 200)->nullable();
            $table->string('term', 200)->nullable();
            $table->string('content', 200)->nullable();
            $table->string('first_source', 120)->nullable();
            $table->string('first_medium', 120)->nullable();
            $table->string('first_campaign', 200)->nullable();
            $table->jsonb('click_ids')->nullable();
            $table->string('landing_url', 1000)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_seen_at']);
        });

        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('event_name', 60)->index();
            $table->string('event_source', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_id', 64)->nullable()->index();
            $table->string('session_id', 64)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number', 60)->nullable()->index();
            $table->string('product_external_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('received_at')->useCurrent();
            $table->string('currency', 3)->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->foreignId('attribution_id')->nullable()->constrained('marketing_attributions')->nullOnDelete();
            $table->jsonb('metadata')->nullable();
            $table->string('consent_state', 20)->nullable();
            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });

        Schema::create('marketing_event_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_event_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 60)->index();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('provider_event_id', 120)->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code', 120)->nullable();
            $table->timestamps();

            $table->unique(['marketing_event_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_event_deliveries');
        Schema::dropIfExists('marketing_events');
        Schema::dropIfExists('marketing_attributions');
    }
};
