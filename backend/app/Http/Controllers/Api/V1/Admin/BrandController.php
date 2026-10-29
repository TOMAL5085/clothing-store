<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], ['draft', 'published', 'archived']))],
            'is_active' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $brands = Brand::query()
            ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return BrandResource::collection($brands);
    }

    public function store(StoreBrandRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $brand = Brand::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('brand.created', $request->user(), $brand, [
            'slug' => $brand->slug,
            'name' => $brand->name,
        ]);

        return response()->json(['data' => BrandResource::make($brand)], 201);
    }

    public function show(Brand $brand): BrandResource
    {
        Gate::authorize('viewAny', Order::class);

        return BrandResource::make($brand->load('products'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand, AuditLogger $audit): BrandResource
    {
        Gate::authorize('viewAny', Order::class);

        $brand->update([
            ...$request->validated(),
        ]);

        $audit->log('brand.updated', $request->user(), $brand->refresh(), [
            'slug' => $brand->slug,
            'name' => $brand->name,
            'is_active' => $brand->is_active,
        ]);

        return BrandResource::make($brand->refresh());
    }

    public function destroy(Brand $brand, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $slug = $brand->slug;
        $brand->delete();

        $audit->log('brand.deleted', request()->user(), null, ['slug' => $slug]);

        return response()->json(['message' => 'Brand deleted.']);
    }
}
