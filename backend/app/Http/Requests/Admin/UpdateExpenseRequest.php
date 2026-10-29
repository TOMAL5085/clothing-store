<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        $expense = $this->route('expense');

        return [
            'reference_number' => ['sometimes', 'string', 'max:40', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', Rule::unique('expenses', 'reference_number')->ignore($expense?->id)],
            'account_id' => ['sometimes', 'integer', 'exists:accounts,id'],
            'category' => ['sometimes', 'string', 'max:80'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'expense_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', Rule::in(Expense::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
