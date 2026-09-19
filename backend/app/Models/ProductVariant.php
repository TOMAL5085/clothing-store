<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'size_id', 'color_id', 'sku', 'price', 'stock_quantity', 'low_stock_threshold', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function getInventoryStatusAttribute(): string
    {
        if (! $this->is_active || $this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        return $this->stock_quantity <= $this->low_stock_threshold ? 'low_stock' : 'in_stock';
    }
}
