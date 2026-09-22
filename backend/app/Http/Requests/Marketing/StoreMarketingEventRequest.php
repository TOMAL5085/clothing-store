<?php

namespace App\Http\Requests\Marketing;

use App\Services\Marketing\MarketingEventCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Structural validation only. Semantic rules (catalog membership,
     * server-only rejection, metadata allowlists, identity checks) live in
     * MarketingEventService so they cannot drift between callers.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['sometimes', 'nullable', 'uuid', 'max:64'],
            'event_name' => ['required', 'string', 'max:60', Rule::in(MarketingEventCatalog::names())],
            'occurred_at' => ['sometimes', 'nullable', 'date'],
            'anonymous_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'session_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'product_external_id' => ['sometimes', 'nullable', 'string', 'max:120'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'metadata' => ['sometimes', 'nullable', 'array', 'max:25'],
            'metadata.*' => ['nullable'],
            'consent' => ['sometimes', 'nullable', 'array'],
            'consent.analytics' => ['sometimes', 'boolean'],
            'consent.marketing' => ['sometimes', 'boolean'],
            'attribution' => ['sometimes', 'nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Identity always comes from Sanctum, never the request body.
        $this->offsetUnset('user_id');
        $this->offsetUnset('user');
    }
}
