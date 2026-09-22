<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Audit rows only ever contain allowlisted metadata (see AuditLogger),
     * so the full record is safe to expose to administrators.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor_id' => $this->actor_id,
            'actor_role' => $this->actor_role,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
