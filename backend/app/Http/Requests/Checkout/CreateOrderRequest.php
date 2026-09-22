<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $demo = config('payments.driver', 'demo') === 'demo';
        $addressRequired = $demo ? 'required_without:shipping_address_id' : 'required_without:shipping_address_id';

        return [
            'cart_token' => ['nullable', 'uuid'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'delivery_method' => ['required', 'in:standard,express'],
            'shipping_address_id' => ['nullable', 'integer'],
            'billing_address_id' => ['nullable', 'integer'],
            'shipping_address.firstName' => [$addressRequired, 'string', 'max:255'],
            'shipping_address.lastName' => [$addressRequired, 'string', 'max:255'],
            'shipping_address.email' => [$addressRequired, 'email', 'max:255'],
            'shipping_address.address' => [$addressRequired, 'string', 'min:4', 'max:255'],
            'shipping_address.city' => [$addressRequired, 'string', 'max:255'],
            'shipping_address.postalCode' => [$addressRequired, 'string', 'min:3', 'max:50'],
            'shipping_address.country' => [$addressRequired, 'string', 'max:255'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.firstName' => ['required_with:billing_address.country', 'nullable', 'string', 'max:255'],
            'billing_address.lastName' => ['required_with:billing_address.country', 'nullable', 'string', 'max:255'],
            'billing_address.email' => ['required_with:billing_address.country', 'nullable', 'email', 'max:255'],
            'billing_address.address' => ['required_with:billing_address.country', 'nullable', 'string', 'min:4', 'max:255'],
            'billing_address.city' => ['required_with:billing_address.country', 'nullable', 'string', 'max:255'],
            'billing_address.postalCode' => ['required_with:billing_address.country', 'nullable', 'string', 'min:3', 'max:50'],
            'billing_address.country' => ['nullable', 'string', 'max:255'],
            'payment.card_name' => [$demo ? 'required' : 'nullable', 'string', 'max:255'],
            'payment.card_number' => [$demo ? 'required' : 'nullable', 'string'],
            'payment.expiry' => [$demo ? 'required' : 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'payment.cvc' => [$demo ? 'required' : 'nullable', 'regex:/^\d{3,4}$/'],
            'anonymous_id' => ['nullable', 'string', 'max:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->offsetUnset('payment_provider');
        $this->offsetUnset('provider');
    }
}
