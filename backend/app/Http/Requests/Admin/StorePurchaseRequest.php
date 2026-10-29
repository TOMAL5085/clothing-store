<?php

namespace App\Http\Requests\Admin;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'reference_number' => ['required', 'string', 'max:120', 'unique:purchases,reference_number'],
            'status' => ['sometimes', 'string', Rule::in(Purchase::STATUSES)],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'payment_status' => ['sometimes', 'string', Rule::in(Purchase::PAYMENT_STATUSES)],
            'expected_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
