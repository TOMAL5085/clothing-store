<?php

namespace App\Http\Requests\Admin;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function rules(): array
    {
        $account = $this->route('account');

        return [
            'code' => ['sometimes', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', Rule::unique('accounts', 'code')->ignore($account?->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(Account::TYPES)],
            'subtype' => ['nullable', 'string', 'max:40'],
            'opening_balance' => ['sometimes', 'numeric', 'min:0'],
            'current_balance' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
