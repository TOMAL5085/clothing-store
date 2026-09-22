<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category' => $this['category'],
            'label' => $this['label'],
            'in_app_enabled' => $this['in_app_enabled'],
            'email_enabled' => $this['email_enabled'],
            'sms_enabled' => $this['sms_enabled'],
            'whatsapp_enabled' => $this['whatsapp_enabled'],
        ];
    }
}
