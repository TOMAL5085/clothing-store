<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    /**
     * Allow storefront-relative paths ("/shop?category=men") and absolute
     * http(s) URLs. Reject javascript:, data:, vbscript:, file: and any
     * other scheme that could execute code from CMS-driven links.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return;
        }

        $fail('The :attribute must be a relative path or an http(s) URL.');
    }
}
