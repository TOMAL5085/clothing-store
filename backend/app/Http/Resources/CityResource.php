<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zone_id,
            'zone' => $this->whenLoaded('zone', fn () => ['id' => $this->zone->id, 'name' => $this->zone->name, 'code' => $this->zone->code]),
            'name' => $this->name,
            'code' => $this->code,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
