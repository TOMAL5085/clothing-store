<?php

namespace App\Http\Requests\Admin;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        $purchase = $this->route('purchase');

        return [
            'supplier_id' => ['sometimes', 'integer', 'exists:suppliers,id'],
            'reference_number' => ['sometimes', 'string', 'max:120', Rule::unique('purchases', 'reference_number')->ignore($purchase?->id)],
            'status' => ['sometimes', 'string', Rule::in(Purchase::STATUSES)],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'payment_status' => ['sometimes', 'string', Rule::in(Purchase::PAYMENT_STATUSES)],
            'expected_date' => ['sometimes', 'date'],
            'received_at' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
