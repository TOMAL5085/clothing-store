<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order?->number,
            'status' => $this->status,
            'reason' => $this->reason,
            'adminReason' => $this->admin_reason,
            'requestedAt' => $this->requested_at?->toISOString(),
            'reviewedAt' => $this->reviewed_at?->toISOString(),
            'receivedAt' => $this->received_at?->toISOString(),
            'resolvedAt' => $this->resolved_at?->toISOString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'orderItemId' => $item->order_item_id,
                'productName' => $item->orderItem?->product_name,
                'productId' => $item->orderItem?->product_external_id,
                'size' => $item->orderItem?->size,
                'quantity' => $item->quantity,
                'resolutionStatus' => $item->resolution_status,
            ])->values()),
            'refund' => $this->whenLoaded('refund', fn () => $this->refund ? RefundResource::make($this->refund) : null),
        ];
    }
}
