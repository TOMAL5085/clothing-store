<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('verified_purchase')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'status']);
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_range CHECK (rating >= 1 AND rating <= 5)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
