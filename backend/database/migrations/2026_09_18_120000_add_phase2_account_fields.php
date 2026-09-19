<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('active')->index()->after('role');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('label')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_default')->default(false)->index()->after('country');
        });

        Schema::create('user_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose')->default('email_verification')->index();
            $table->string('channel')->default('email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_otps');

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['label', 'phone', 'is_default']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'status']);
        });
    }
};
