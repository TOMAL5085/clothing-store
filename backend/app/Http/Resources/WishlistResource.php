<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->guest_token,
            'ids' => $this->items->pluck('product.external_id')->filter()->values(),
            'products' => ProductResource::collection($this->items->pluck('product')->filter()),
        ];
    }
}
