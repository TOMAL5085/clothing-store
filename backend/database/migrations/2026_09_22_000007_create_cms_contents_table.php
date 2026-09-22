<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('type', 40)->index();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('mobile_image_path', 500)->nullable();
            $table->string('cta_label', 120)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_contents');
    }
};
