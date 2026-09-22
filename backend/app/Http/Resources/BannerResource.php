<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Public-safe shape: renderable slide fields plus scheduling state,
     * never creator/admin identifiers.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'body' => $this->body,
            'image_url' => $this->image_url,
            'mobile_image_url' => $this->mobile_image_url,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
