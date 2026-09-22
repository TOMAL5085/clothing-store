<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ModerateReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approved,rejected'],
        ];
    }
}
