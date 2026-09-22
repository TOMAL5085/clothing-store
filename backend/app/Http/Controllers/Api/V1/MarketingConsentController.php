<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketingConsentController extends Controller
{
    /**
     * The caller's stored marketing consent. Absent means undecided, which
     * the pipeline treats as denied — consent is opt-in, never assumed.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->state($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill([
            'marketing_consent' => [
                'analytics' => (bool) $validated['analytics'],
                'marketing' => (bool) $validated['marketing'],
                'updated_at' => now()->toIso8601String(),
            ],
        ])->save();

        return response()->json(['data' => $this->state($request)]);
    }

    /** @return array{analytics:bool, marketing:bool} */
    private function state(Request $request): array
    {
        $stored = $request->user()->marketing_consent;

        return [
            'analytics' => (bool) (is_array($stored) ? ($stored['analytics'] ?? false) : false),
            'marketing' => (bool) (is_array($stored) ? ($stored['marketing'] ?? false) : false),
        ];
    }
}
