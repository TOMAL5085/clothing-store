<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesSchedule;
use App\Models\Banner;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    use ValidatesSchedule;

    public function rules(): array
    {
        $banner = $this->route('banner');

        return array_merge([
            'key' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('banners', 'key')->ignore($banner?->id)],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'image_path' => ['sometimes', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'status' => ['sometimes', 'string', Rule::in(Banner::STATUSES)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ], $this->scheduleRules());
    }
}
