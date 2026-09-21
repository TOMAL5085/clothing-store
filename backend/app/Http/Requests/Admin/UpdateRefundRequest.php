<?php

namespace App\Http\Requests\Admin;

use App\Models\Refund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRefundRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['processing', 'succeeded', 'failed', 'canceled'])],
            'provider_reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'failure_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
