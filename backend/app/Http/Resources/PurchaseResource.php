<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => ['id' => $this->supplier->id, 'name' => $this->supplier->name, 'slug' => $this->supplier->slug]),
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'payment_status' => $this->payment_status,
            'expected_date' => $this->expected_date?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'notes' => $this->notes,
            'item_count' => $this->whenCounted('items'),
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
