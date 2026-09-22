<?php

namespace App\Http\Requests\Admin;

use App\Services\Analytics\AnalyticsRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnalyticsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'preset' => ['sometimes', 'string', Rule::in(AnalyticsRange::PRESETS)],
            'from' => ['sometimes', 'date_format:Y-m-d', 'required_with:to', 'before_or_equal:to'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'required_with:from', 'after_or_equal:from'],
            'group' => ['sometimes', 'string', Rule::in(AnalyticsRange::GROUPS)],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'sort' => ['sometimes', 'string', Rule::in(['quantity', 'revenue'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $validator->getData();

                if (! isset($data['from'], $data['to'])) {
                    return;
                }

                $span = abs(strtotime($data['to']) - strtotime($data['from']));

                if ($span > 366 * 86400) {
                    $validator->errors()->add('to', 'The custom date range may not span more than 366 days.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'from.before_or_equal' => 'The start date must not be after the end date.',
        ];
    }

    /**
     * @return array{preset?:string, from?:string, to?:string, group?:string}
     */
    public function rangeInput(): array
    {
        return array_filter($this->only(['preset', 'from', 'to', 'group']), fn ($value) => $value !== null);
    }
}
