<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesSchedule;
use App\Models\CmsContent;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCmsContentRequest extends FormRequest
{
    use ValidatesSchedule;

    public function rules(): array
    {
        return array_merge([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:cms_contents,key'],
            'type' => ['required', 'string', Rule::in(CmsContent::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'status' => ['sometimes', 'string', Rule::in(CmsContent::STATUSES)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ], $this->scheduleRules());
    }
}
