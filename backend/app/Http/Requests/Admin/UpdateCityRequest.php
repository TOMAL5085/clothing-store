<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
{
    public function rules(): array
    {
        $city = $this->route('city');

        return [
            'zone_id' => ['sometimes', 'integer', 'exists:zones,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('cities', 'code')->ignore($city?->id)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
