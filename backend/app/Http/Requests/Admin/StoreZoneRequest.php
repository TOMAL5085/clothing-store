<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', 'unique:zones,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['sometimes', 'numeric', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
