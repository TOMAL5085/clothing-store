<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('wholesale_customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('courier_reference')->nullable();
            $table->timestamp('invoice_emailed_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->text('internal_note')->nullable();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['wholesale_customer_id']);
            $table->dropColumn(['wholesale_customer_id', 'courier_reference', 'invoice_emailed_at', 'disbursed_at', 'internal_note']);
        });
    }
};
