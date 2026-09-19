<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->decimal('minimum_order_amount', 10, 2)->default(0)->after('expires_at');
            $table->decimal('maximum_discount_amount', 10, 2)->nullable()->after('minimum_order_amount');
            $table->unsignedInteger('usage_limit')->nullable()->after('maximum_discount_amount');
            $table->unsignedInteger('used_count')->default(0)->after('usage_limit');
            $table->unsignedInteger('per_customer_limit')->nullable()->after('used_count');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'starts_at',
                'minimum_order_amount',
                'maximum_discount_amount',
                'usage_limit',
                'used_count',
                'per_customer_limit',
            ]);
        });
    }
};
