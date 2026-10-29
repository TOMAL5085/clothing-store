<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wholesale_customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wholesale_customer_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wholesale_customer_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->timestamps();
            $table->index(['wholesale_customer_id', 'entry_date']);
        });

        Schema::create('weight_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_weight', 10, 3);
            $table->decimal('max_weight', 10, 3)->nullable();
            $table->decimal('price', 14, 2);
            $table->string('unit')->default('kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visitor_counts', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('period')->default('daily');
            $table->string('ip_hash', 64);
            $table->string('path')->nullable();
            $table->timestamps();
            $table->unique(['visit_date', 'period', 'ip_hash']);
            $table->index(['visit_date', 'period']);
        });

        Schema::create('fraud_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('risk_level')->default('low');
            $table->unsignedSmallInteger('risk_score')->default(0);
            $table->json('signals')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique('order_id');
        });

        Schema::create('order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['staff_id', 'status']);
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('order_assignments');
        Schema::dropIfExists('fraud_checks');
        Schema::dropIfExists('visitor_counts');
        Schema::dropIfExists('weight_price_rules');
        Schema::dropIfExists('wholesale_customer_ledger');
        Schema::dropIfExists('wholesale_customers');
    }
};
