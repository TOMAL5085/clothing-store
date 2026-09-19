<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cart_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->after('shipping_address_id')->constrained('addresses')->nullOnDelete();
            $table->char('country_code', 2)->nullable()->after('delivery_method')->index();
            $table->string('payment_provider')->nullable()->after('country_code')->index();
            $table->char('currency', 3)->default('USD')->after('payment_provider');
            $table->uuid('checkout_token')->nullable()->unique()->after('currency');
            $table->timestamp('inventory_decremented_at')->nullable()->after('total');
            $table->timestamp('coupon_consumed_at')->nullable()->after('inventory_decremented_at');
            $table->timestamp('cart_cleared_at')->nullable()->after('coupon_consumed_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_event_id')->nullable()->after('reference');
            $table->string('method')->nullable()->after('provider_event_id');
            $table->unsignedInteger('amount_minor')->nullable()->after('amount');
            $table->json('payload')->nullable()->after('failure_reason');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('provider_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_event_id']);
            $table->dropColumn(['provider_event_id', 'method', 'amount_minor', 'payload']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_id');
            $table->dropConstrainedForeignId('billing_address_id');
            $table->dropColumn([
                'country_code',
                'payment_provider',
                'currency',
                'checkout_token',
                'inventory_decremented_at',
                'coupon_consumed_at',
                'cart_cleared_at',
            ]);
        });
    }
};
