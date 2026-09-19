<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'q' => ['nullable', 'string', 'max:255'],
            'sizes' => ['nullable'],
            'colors' => ['nullable'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'featured', 'new', 'bestseller', 'sale'])],
            'sort' => ['nullable', Rule::in(['featured', 'newest', 'oldest', 'price-asc', 'price-desc', 'rating', 'name-asc', 'name-desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Product::query()->active()->with(['category', 'images', 'variants.size', 'variants.color']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $validated['category']));
        }

        if ($request->filled('q')) {
            $term = '%'.strtolower($validated['q']).'%';
            $query->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(sku) LIKE ?', [$term])
                ->orWhereRaw('LOWER(brand) LIKE ?', [$term])
                ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                ->orWhereHas('category', fn ($c) => $c->whereRaw('LOWER(slug) LIKE ? OR LOWER(label) LIKE ?', [$term, $term]))
                ->orWhereHas('variants', fn ($v) => $v->whereRaw('LOWER(sku) LIKE ?', [$term])));
        }

        foreach ($this->arrayInput($request->input('sizes', [])) as $size) {
            $query->whereHas('variants.size', fn ($q) => $q->where('name', $size));
        }

        foreach ($this->arrayInput($request->input('colors', [])) as $color) {
            $query->whereHas('variants.color', fn ($q) => $q->where('name', $color));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        if ($request->boolean('in_stock')) {
            $query->where('in_stock', true)->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('stock_quantity', '>', 0));
        }

        match ($request->input('status')) {
            'active' => $query->where('is_active', true),
            'featured' => $query->where('is_featured', true),
            'new' => $query->where('is_new', true),
            'bestseller' => $query->where('is_bestseller', true),
            'sale' => $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'),
            default => null,
        };

        match ($request->input('sort', 'featured')) {
            'newest' => $query->orderByDesc('is_new')->latest(),
            'oldest' => $query->oldest(),
            'price-asc' => $query->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating'),
            'name-asc' => $query->orderBy('name'),
            'name-desc' => $query->orderByDesc('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_bestseller')->latest(),
        };

        return ProductResource::collection($query->paginate((int) $request->input('per_page', 24)));
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return ProductResource::make($product->load(['category', 'images', 'variants.size', 'variants.color']));
    }

    public function featured()
    {
        return ProductResource::collection(Product::active()->where('is_featured', true)->with(['category', 'images', 'variants.size', 'variants.color'])->limit(12)->get());
    }

    public function related(Product $product)
    {
        return ProductResource::collection(Product::active()->where('category_id', $product->category_id)->whereKeyNot($product->id)->with(['category', 'images', 'variants.size', 'variants.color'])->limit(4)->get());
    }

    private function arrayInput(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter($value);
        }

        return array_filter(array_map('trim', explode(',', (string) $value)));
    }
}
