<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'parent_id' => ['sometimes', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $parentFilter = $validated['parent_id'] ?? null;

        $categories = Category::query()
            ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when(array_key_exists('parent_id', $validated), function ($query) use ($parentFilter) {
                if ($parentFilter === 'null') {
                    $query->whereNull('parent_id');
                } elseif ($parentFilter === 'not_null') {
                    $query->whereNotNull('parent_id');
                } else {
                    $query->where('parent_id', $parentFilter);
                }
            })
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('label', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $category = Category::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('category.created', $request->user(), $category, [
            'slug' => $category->slug,
            'label' => $category->label,
            'parent_id' => $category->parent_id,
        ]);

        return response()->json(['data' => CategoryResource::make($category)], 201);
    }

    public function show(Category $category): CategoryResource
    {
        Gate::authorize('viewAny', Order::class);

        return CategoryResource::make($category->load('children'));
    }

    public function update(UpdateCategoryRequest $request, Category $category, AuditLogger $audit): CategoryResource
    {
        Gate::authorize('viewAny', Order::class);

        $category->update([
            ...$request->validated(),
        ]);

        $audit->log('category.updated', $request->user(), $category->refresh(), [
            'slug' => $category->slug,
            'label' => $category->label,
            'is_active' => $category->is_active,
        ]);

        return CategoryResource::make($category->refresh());
    }

    public function destroy(Category $category, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $slug = $category->slug;
        $category->delete();

        $audit->log('category.deleted', request()->user(), null, ['slug' => $slug]);

        return response()->json(['message' => 'Category deleted.']);
    }
}
