<?php

namespace App\Http\Requests\Admin;

use App\Models\ManualPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManualPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        $payment = $this->route('manualPayment');

        return [
            'reference_number' => ['sometimes', 'string', 'max:40', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', Rule::unique('manual_payments', 'reference_number')->ignore($payment?->id)],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'account_id' => ['sometimes', 'integer', 'exists:accounts,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['sometimes', 'string', Rule::in(ManualPayment::PAYMENT_METHODS)],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', Rule::in(ManualPayment::STATUSES)],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
