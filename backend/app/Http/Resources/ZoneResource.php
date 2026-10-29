<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'shipping_cost' => (float) $this->shipping_cost,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'city_count' => $this->whenCounted('cities'),
        ];
    }
}
