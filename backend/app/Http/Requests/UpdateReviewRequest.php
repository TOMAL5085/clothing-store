<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'body' => ['sometimes', 'string', 'max:2000'],
        ];
    }
}
