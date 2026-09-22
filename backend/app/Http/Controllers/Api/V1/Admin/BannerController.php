<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\ContentMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], Banner::STATUSES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $banners = Banner::query()
            ->when(($validated['status'] ?? 'all') !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('key', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return BannerResource::collection($banners);
    }

    public function store(StoreBannerRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $banner = Banner::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('banner.created', $request->user(), $banner, [
            'key' => $banner->key,
            'title' => $banner->title,
        ]);
        ContentMediaService::bumpVersion();

        return response()->json(['data' => BannerResource::make($banner)], 201);
    }

    public function show(Banner $banner): BannerResource
    {
        Gate::authorize('viewAny', Order::class);

        return BannerResource::make($banner);
    }

    public function update(UpdateBannerRequest $request, Banner $banner, AuditLogger $audit): BannerResource
    {
        Gate::authorize('viewAny', Order::class);

        $banner->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('banner.updated', $request->user(), $banner->refresh(), [
            'key' => $banner->key,
            'status' => $banner->status,
        ]);
        ContentMediaService::bumpVersion();

        return BannerResource::make($banner->refresh());
    }

    public function destroy(Banner $banner, AuditLogger $audit, ContentMediaService $media): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $key = $banner->key;
        $media->deleteManagedAssets($banner);
        $banner->delete();

        $audit->log('banner.deleted', request()->user(), null, ['key' => $key]);
        ContentMediaService::bumpVersion();

        return response()->json(['message' => 'Banner deleted.']);
    }

    public function uploadImage(Request $request, Banner $banner, AuditLogger $audit, ContentMediaService $media): BannerResource|JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        if (! isset($validated['image']) && ! isset($validated['mobile_image'])) {
            return response()->json(['message' => 'Provide an image or mobile_image file.'], 422);
        }

        $media->replaceManagedAssets($banner, $validated, 'banners');
        $banner->update(['updated_by' => $request->user()->id]);

        $audit->log('banner.media_uploaded', $request->user(), $banner->refresh(), ['key' => $banner->key]);
        ContentMediaService::bumpVersion();

        return BannerResource::make($banner->refresh());
    }
}
