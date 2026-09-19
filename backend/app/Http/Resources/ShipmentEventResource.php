<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentEventResource extends JsonResource
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
            'location' => $this->location,
            'description' => $this->description,
            'occurredAt' => $this->occurred_at?->toISOString(),
        ];
    }
}