<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variants = $this->resource->relationLoaded('variants') ? $this->variants : collect();
        $colors = collect($variants)->pluck('color')->filter()->unique('name')->values();
        $sizes = collect($variants)->pluck('size')->filter()->sortBy('sort_order')->pluck('name')->unique()->values();

        return [
            'id' => $this->external_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category?->slug,
            'price' => (float) $this->price,
            'compareAt' => $this->compare_at_price !== null ? (float) $this->compare_at_price : null,
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews_count,
            'colors' => $colors->map(fn ($color) => ['name' => $color->name, 'hex' => $color->hex])->all(),
            'sizes' => $sizes->all(),
            'images' => $this->images->pluck('url')->all(),
            'alt' => $this->images->first()?->alt ?? $this->name,
            'badge' => $this->badge,
            'isNew' => $this->is_new,
            'bestseller' => $this->is_bestseller,
            'description' => $this->description,
            'details' => $this->details ?? [],
            'inStock' => $this->in_stock && $this->stock_quantity > 0,
            'stockQuantity' => $this->stock_quantity,
            'availableStock' => $variants->sum('stock_quantity'),
            'lowStockThreshold' => $this->low_stock_threshold,
            'inventoryStatus' => $this->inventory_status,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'variants' => collect($variants)->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size?->name,
                'color' => $variant->color ? ['name' => $variant->color->name, 'hex' => $variant->color->hex] : null,
                'price' => $variant->price !== null ? (float) $variant->price : null,
                'stockQuantity' => $variant->stock_quantity,
                'lowStockThreshold' => $variant->low_stock_threshold,
                'inventoryStatus' => $variant->inventory_status,
                'isActive' => $variant->is_active,
            ])->values()->all(),
        ];
    }
}
