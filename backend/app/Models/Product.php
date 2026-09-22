<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'category_id',
        'slug',
        'name',
        'description',
        'short_description',
        'price',
        'compare_at_price',
        'sku',
        'brand',
        'rating',
        'reviews_count',
        'badge',
        'is_new',
        'is_bestseller',
        'is_featured',
        'is_active',
        'in_stock',
        'stock_quantity',
        'low_stock_threshold',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'rating' => 'decimal:2',
            'details' => 'array',
            'is_new' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'in_stock' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getInventoryStatusAttribute(): string
    {
        if (! $this->in_stock || $this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        return $this->stock_quantity <= $this->low_stock_threshold ? 'low_stock' : 'in_stock';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
