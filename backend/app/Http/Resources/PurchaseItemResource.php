<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => ['id' => $this->product->id, 'name' => $this->product->name, 'slug' => $this->product->slug]),
            'variant_id' => $this->variant_id,
            'variant' => $this->whenLoaded('variant', fn () => ['id' => $this->variant->id, 'sku' => $this->variant->sku]),
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,
        ];
    }
}
