<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketingEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->event_id,
            'event_name' => $this->event_name,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'accepted' => true,
        ];
    }
}
