<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\CartService;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->items->map(function ($item) {
            $price = (float) ($item->variant?->price ?? $item->product->price);
            $availableStock = min($item->product->stock_quantity, $item->variant?->stock_quantity ?? 0);

            return [
                'id' => "{$item->product->external_id}__{$item->product_variant_id}",
                'legacyId' => "{$item->product->external_id}__{$item->size}",
                'databaseId' => $item->id,
                'productId' => $item->product->external_id,
                'productName' => $item->product->name,
                'productSlug' => $item->product->slug,
                'image' => $item->product->images->first()?->url,
                'size' => $item->size,
                'color' => $item->variant?->color?->name,
                'sku' => $item->variant?->sku ?? $item->product->sku,
                'qty' => $item->quantity,
                'quantity' => $item->quantity,
                'unitPrice' => $price,
                'lineTotal' => round($price * $item->quantity, 2),
                'availableStock' => $availableStock,
                'inStock' => $item->product->in_stock && $availableStock >= $item->quantity,
                'product' => ProductResource::make($item->product),
            ];
        });

        $cartService = app(CartService::class);
        $summary = $cartService->totals($this->resource);
        $coupon = $cartService->coupon($this->resource);

        return [
            'token' => $this->guest_token,
            'items' => $items->values(),
            'promo' => $this->promo_code,
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
            ] : null,
            'summary' => $summary,
        ];
    }
}
