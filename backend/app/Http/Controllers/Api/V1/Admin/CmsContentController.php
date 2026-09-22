<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCmsContentRequest;
use App\Http\Requests\Admin\UpdateCmsContentRequest;
use App\Http\Resources\CmsContentResource;
use App\Models\CmsContent;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\ContentMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CmsContentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], CmsContent::STATUSES))],
            'type' => ['sometimes', 'string', Rule::in(array_merge(['all'], CmsContent::TYPES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $contents = CmsContent::query()
            ->when(($validated['status'] ?? 'all') !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when(($validated['type'] ?? 'all') !== 'all', fn ($query) => $query->where('type', $validated['type']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('key', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return CmsContentResource::collection($contents);
    }

    public function store(StoreCmsContentRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $content = CmsContent::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('cms.created', $request->user(), $content, [
            'key' => $content->key,
            'type' => $content->type,
        ]);
        ContentMediaService::bumpVersion();

        return response()->json(['data' => CmsContentResource::make($content)], 201);
    }

    public function show(CmsContent $content): CmsContentResource
    {
        Gate::authorize('viewAny', Order::class);

        return CmsContentResource::make($content);
    }

    public function update(UpdateCmsContentRequest $request, CmsContent $content, AuditLogger $audit): CmsContentResource
    {
        Gate::authorize('viewAny', Order::class);

        $content->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('cms.updated', $request->user(), $content->refresh(), [
            'key' => $content->key,
            'status' => $content->status,
        ]);
        ContentMediaService::bumpVersion();

        return CmsContentResource::make($content->refresh());
    }

    public function destroy(CmsContent $content, AuditLogger $audit, ContentMediaService $media): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $key = $content->key;
        $media->deleteManagedAssets($content);
        $content->delete();

        $audit->log('cms.deleted', request()->user(), null, ['key' => $key]);
        ContentMediaService::bumpVersion();

        return response()->json(['message' => 'Content deleted.']);
    }

    public function uploadImage(Request $request, CmsContent $content, AuditLogger $audit, ContentMediaService $media): CmsContentResource|JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        if (! isset($validated['image']) && ! isset($validated['mobile_image'])) {
            return response()->json(['message' => 'Provide an image or mobile_image file.'], 422);
        }

        $media->replaceManagedAssets($content, $validated, 'cms');
        $content->update(['updated_by' => $request->user()->id]);

        $audit->log('cms.media_uploaded', $request->user(), $content->refresh(), ['key' => $content->key]);
        ContentMediaService::bumpVersion();

        return CmsContentResource::make($content->refresh());
    }
}
