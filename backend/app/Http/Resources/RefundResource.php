<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
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
            'provider' => $this->provider,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'providerReference' => $this->when($request->user()?->isAdmin(), $this->provider_reference),
            'failureReason' => $this->failure_reason,
            'requestedAt' => $this->requested_at?->toISOString(),
            'processedAt' => $this->processed_at?->toISOString(),
        ];
    }
}
