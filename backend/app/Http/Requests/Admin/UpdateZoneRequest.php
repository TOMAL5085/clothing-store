<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZoneRequest extends FormRequest
{
    public function rules(): array
    {
        $zone = $this->route('zone');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', Rule::unique('zones', 'code')->ignore($zone?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['sometimes', 'numeric', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
