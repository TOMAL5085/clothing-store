<?php

namespace App\Http\Requests;

use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1', 'max:50'],
            'preferences.*.category' => ['required', 'string', Rule::in(array_keys(NotificationPreferenceService::catalog()))],
            'preferences.*.in_app_enabled' => ['sometimes', 'boolean'],
            'preferences.*.email_enabled' => ['sometimes', 'boolean'],
            'preferences.*.sms_enabled' => ['sometimes', 'boolean'],
            'preferences.*.whatsapp_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Unknown preference fields are rejected outright rather than silently
     * dropped, so clients cannot probe for unsupported channels.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $allowed = ['category', 'in_app_enabled', 'email_enabled', 'sms_enabled', 'whatsapp_enabled'];

                foreach ((array) $this->input('preferences', []) as $index => $preference) {
                    if (! is_array($preference)) {
                        continue;
                    }

                    $unknown = array_diff(array_keys($preference), $allowed);

                    if ($unknown !== []) {
                        $validator->errors()->add(
                            "preferences.{$index}",
                            'Unknown preference field(s): '.implode(', ', $unknown).'.'
                        );
                    }
                }
            },
        ];
    }
}
