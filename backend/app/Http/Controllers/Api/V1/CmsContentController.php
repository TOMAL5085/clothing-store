<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CmsContentResource;
use App\Models\CmsContent;
use App\Services\ContentMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class CmsContentController extends Controller
{
    /**
     * Public content listing: published, scheduled, ordered. Filterable by
     * type or exact key. Cached briefly as resolved arrays (never Eloquent
     * models) with a version key that admin mutations bump.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(CmsContent::TYPES)],
            'key' => ['sometimes', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $version = ContentMediaService::version();
        $type = $validated['type'] ?? 'all';
        $key = $validated['key'] ?? 'all';
        $perPage = $validated['per_page'] ?? 50;
        $cacheKey = "cms:public:v{$version}:{$type}:{$key}:{$perPage}";

        $contents = Cache::remember($cacheKey, 60, function () use ($validated, $perPage, $request) {
            return CmsContentResource::collection(
                CmsContent::query()
                    ->visible()
                    ->when(isset($validated['type']), fn ($query) => $query->where('type', $validated['type']))
                    ->when(isset($validated['key']), fn ($query) => $query->where('key', $validated['key']))
                    ->ordered()
                    ->limit($perPage)
                    ->get()
            )->toArray($request);
        });

        return response()->json(['data' => $contents]);
    }
}
