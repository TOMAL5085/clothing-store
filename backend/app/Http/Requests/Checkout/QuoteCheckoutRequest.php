<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class QuoteCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cart_token' => ['nullable', 'uuid'],
            'delivery_method' => ['nullable', 'in:standard,express'],
            'shipping_address_id' => ['nullable', 'integer'],
            'shipping_address.country' => ['required_without:shipping_address_id', 'string', 'max:255'],
            'shipping_address.firstName' => ['nullable', 'string', 'max:255'],
            'shipping_address.lastName' => ['nullable', 'string', 'max:255'],
            'shipping_address.email' => ['nullable', 'email', 'max:255'],
            'shipping_address.address' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:255'],
            'shipping_address.postalCode' => ['nullable', 'string', 'max:50'],
        ];
    }
}
