<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends StoreAddressRequest
{
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:80'],
            'firstName' => ['sometimes', 'required', 'string', 'max:255'],
            'lastName' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'required', 'string', 'min:4', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'postalCode' => ['sometimes', 'required', 'string', 'min:3', 'max:50'],
            'country' => ['sometimes', 'required', 'string', 'max:255'],
            'isDefault' => ['sometimes', 'boolean'],
        ];
    }
}
