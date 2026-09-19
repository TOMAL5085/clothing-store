<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\AdjustInventoryRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductManagementController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:all,active,inactive'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with(['category', 'images', 'variants.size', 'variants.color'])
            ->when(($validated['status'] ?? 'all') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($validated['status'] ?? 'all') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('brand', 'ilike', "%{$search}%")
                        ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('sku', 'ilike', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 100);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(fn () => $this->persistProduct(new Product, $request->validated()));

        return response()->json(['data' => ProductResource::make($product)], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return ProductResource::make(DB::transaction(fn () => $this->persistProduct($product, $request->validated())));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false, 'in_stock' => false]);

        return response()->json(['message' => 'Product deactivated.']);
    }

    public function adjustInventory(AdjustInventoryRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        return ProductResource::make(DB::transaction(function () use ($product, $data) {
            $target = isset($data['variant_id'])
                ? $product->variants()->whereKey($data['variant_id'])->lockForUpdate()->firstOrFail()
                : $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $current = $target->stock_quantity;
            $next = match ($data['mode']) {
                'set' => $data['quantity'],
                'increase' => $current + $data['quantity'],
                'decrease' => $current - $data['quantity'],
            };

            if ($next < 0) {
                throw ValidationException::withMessages(['quantity' => 'Inventory cannot become negative.']);
            }

            $target->stock_quantity = $next;
            if (array_key_exists('low_stock_threshold', $data)) {
                $target->low_stock_threshold = $data['low_stock_threshold'];
            }
            $target->save();

            $this->syncProductInventory($product->refresh());

            return $product->load(['category', 'images', 'variants.size', 'variants.color']);
        }));
    }

    private function persistProduct(Product $product, array $data): Product
    {
        if (isset($data['category'])) {
            $data['category_id'] = Category::where('slug', $data['category'])->value('id');
        }

        $images = $data['images'] ?? null;
        $variants = $data['variants'] ?? null;
        unset($data['images'], $data['variants'], $data['category']);

        $data['external_id'] ??= Str::slug($data['slug'] ?? $data['name']);
        $data['brand'] ??= 'JAAJ';
        $data['details'] ??= [];
        $data['is_active'] ??= true;
        $data['in_stock'] = ($data['stock_quantity'] ?? $product->stock_quantity ?? 0) > 0;

        $product->fill($data)->save();

        if (is_array($images)) {
            $product->images()->delete();
            foreach ($images as $index => $image) {
                $product->images()->create([
                    'url' => $image['url'],
                    'alt' => $image['alt'] ?? $product->name,
                    'sort_order' => $image['sort_order'] ?? $index + 1,
                ]);
            }
        }

        if (is_array($variants)) {
            $seen = [];
            foreach ($variants as $variantData) {
                $size = Size::firstOrCreate(['name' => $variantData['size']], ['sort_order' => 99]);
                $color = isset($variantData['color'])
                    ? Color::firstOrCreate(['name' => $variantData['color']], ['hex' => $variantData['color_hex'] ?? '#000000'])
                    : null;

                $variant = isset($variantData['id'])
                    ? $product->variants()->whereKey($variantData['id'])->firstOrFail()
                    : new ProductVariant(['product_id' => $product->id]);

                $skuExists = ProductVariant::query()
                    ->where('sku', $variantData['sku'])
                    ->when($variant->exists, fn ($query) => $query->whereKeyNot($variant->id))
                    ->exists();

                if ($skuExists) {
                    throw ValidationException::withMessages(['variants' => "Variant SKU {$variantData['sku']} is already in use."]);
                }

                $variant->fill([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'color_id' => $color?->id,
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                    'is_active' => $variantData['is_active'] ?? true,
                ])->save();
                $seen[] = $variant->id;
            }

            $product->variants()->whereNotIn('id', $seen)->update(['is_active' => false, 'stock_quantity' => 0]);
            $this->syncProductInventory($product);
        }

        return $product->load(['category', 'images', 'variants.size', 'variants.color']);
    }

    private function syncProductInventory(Product $product): void
    {
        $total = $product->variants()->where('is_active', true)->sum('stock_quantity');
        $product->update([
            'stock_quantity' => $total,
            'in_stock' => $product->is_active && $total > 0,
        ]);
    }
}
