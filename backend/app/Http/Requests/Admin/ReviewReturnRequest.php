<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'admin_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
