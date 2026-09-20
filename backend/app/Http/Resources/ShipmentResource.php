<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => str($this->status)->headline()->toString(),
            'statusCode' => $this->status,
            'carrier' => $this->carrier,
            'trackingNumber' => $this->tracking_number,
            'trackingReference' => $this->tracking_reference,
            'carrierReference' => $this->carrier_reference,
            'shippingFee' => $this->shipping_fee ? (float) $this->shipping_fee : null,
            'estimatedDeliveryAt' => $this->estimated_delivery_at?->toISOString(),
            'shippedAt' => $this->shipped_at?->toISOString(),
            'deliveredAt' => $this->delivered_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}