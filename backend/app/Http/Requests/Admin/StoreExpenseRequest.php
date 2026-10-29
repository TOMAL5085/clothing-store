<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', 'unique:expenses,reference_number'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'category' => ['required', 'string', 'max:80'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'expense_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', Rule::in(Expense::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
