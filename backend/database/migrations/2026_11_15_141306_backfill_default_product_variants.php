<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sizeId = DB::table('sizes')->where('name', 'One Size')->value('id');
        if (! $sizeId) {
            $sizeId = DB::table('sizes')->insertGetId([
                'name' => 'One Size',
                'sort_order' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = DB::table('products')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('product_variants')
                ->whereColumn('product_variants.product_id', 'products.id'))
            ->get(['id', 'sku', 'price', 'stock_quantity', 'low_stock_threshold']);

        foreach ($products as $product) {
            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'size_id' => $sizeId,
                'color_id' => null,
                'sku' => $product->sku.'-DEFAULT',
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing catalog entries cannot be safely separated from manually created variants.
    }
};
