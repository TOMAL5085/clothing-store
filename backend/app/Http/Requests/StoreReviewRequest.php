<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
