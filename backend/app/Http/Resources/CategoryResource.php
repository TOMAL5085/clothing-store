<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'slug' => $this->slug,
            'label' => $this->label,
            'tagline' => $this->tagline,
            'image' => $this->image,
            'alt' => $this->alt,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'parent' => $this->when($this->parent, function () {
                return [
                    'slug' => $this->parent->slug,
                    'label' => $this->parent->label,
                ];
            }),
            'has_children' => $this->when($this->relationLoaded('children'), $this->children->count(), $this->children()->exists()),
        ];
    }
}
