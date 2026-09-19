<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'emailVerified' => $this->email_verified_at !== null,
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'joinedAt' => $this->created_at?->toISOString(),
            'ordersCount' => $this->whenCounted('orders'),
            'addressesCount' => $this->whenCounted('addresses'),
        ];
    }
}
