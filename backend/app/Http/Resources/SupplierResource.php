<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_id' => $this->tax_id,
            'address' => $this->address,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => ['id' => $this->city->id, 'name' => $this->city->name]),
            'zone_id' => $this->zone_id,
            'zone' => $this->whenLoaded('zone', fn () => ['id' => $this->zone->id, 'name' => $this->zone->name]),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'purchase_count' => $this->whenCounted('purchases'),
        ];
    }
}
