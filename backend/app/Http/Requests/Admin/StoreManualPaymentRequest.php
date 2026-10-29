<?php

namespace App\Http\Requests\Admin;

use App\Models\ManualPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', 'unique:manual_payments,reference_number'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'string', Rule::in(ManualPayment::PAYMENT_METHODS)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', Rule::in(ManualPayment::STATUSES)],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
