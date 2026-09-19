<?php

namespace App\Http\Requests\Admin;

use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(Shipment::STATUSES)],
            'carrier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'estimated_delivery_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}