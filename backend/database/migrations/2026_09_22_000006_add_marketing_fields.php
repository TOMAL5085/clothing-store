<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minimal additive fields for marketing measurement: per-user consent
     * state and the anonymous browser identity captured at checkout so
     * guest purchases can link back to their touchpoint.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('marketing_consent')->nullable()->after('remember_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('anonymous_id', 64)->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('anonymous_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_consent');
        });
    }
};
