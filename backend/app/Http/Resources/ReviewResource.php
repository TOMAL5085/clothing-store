<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Only de-identified customer information is exposed: the display name.
     * User ids, emails and internal moderation notes never leave the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'verifiedPurchase' => (bool) $this->verified_purchase,
            'customerName' => $this->user?->name,
            'productId' => $this->product?->external_id,
            'productSlug' => $this->product?->slug,
            'productName' => $this->product?->name,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
