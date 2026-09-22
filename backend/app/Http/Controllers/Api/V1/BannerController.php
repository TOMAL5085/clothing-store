<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\ContentMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    /**
     * Public slide listing: published, scheduled, ordered. Cached briefly
     * as resolved arrays (never Eloquent models, so cache backends can
     * never trip over model unserialization) with a version key that admin
     * mutations bump, so publishes apply within seconds without hammering
     * the database on every homepage hit.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $version = ContentMediaService::version();
        $perPage = $validated['per_page'] ?? 10;

        $slides = Cache::remember(
            "banners:public:v{$version}:{$perPage}",
            60,
            fn () => BannerResource::collection(
                Banner::query()->visible()->ordered()->limit($perPage)->get()
            )->toArray($request)
        );

        return response()->json(['data' => $slides]);
    }
}
