<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Outbound SMS / WhatsApp delivery records, tracked independently from
     * the Laravel notifications table. One row per message per channel;
     * retries update the row in place instead of inserting duplicates.
     *
     * Only operational metadata is stored: template key + order number for
     * context, never message secrets, credentials, or tokens.
     */
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('message_key', 64);
            $table->uuid('notification_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->index();
            $table->string('recipient', 32);
            $table->string('template', 60)->nullable();
            $table->string('order_number', 60)->nullable()->index();
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider', 60)->nullable();
            $table->string('provider_message_id', 120)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code', 120)->nullable();
            $table->timestamps();

            $table->unique(['message_key', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
