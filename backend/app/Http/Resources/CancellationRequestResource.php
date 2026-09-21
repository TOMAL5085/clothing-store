<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancellationRequestResource extends JsonResource
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
            'executedAt' => $this->executed_at?->toISOString(),
            'refund' => $this->whenLoaded('refund', fn () => $this->refund ? RefundResource::make($this->refund) : null),
        ];
    }
}
