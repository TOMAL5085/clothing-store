<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function show(Request $request): WishlistResource
    {
        return WishlistResource::make($this->load($this->resolve($request)));
    }

    public function store(Request $request): WishlistResource
    {
        $data = $request->validate(['product_id' => ['required', 'string', 'exists:products,external_id'], 'wishlist_token' => ['nullable', 'uuid']]);
        $wishlist = $this->resolve($request);
        $product = Product::where('external_id', $data['product_id'])->firstOrFail();
        abort_unless($product->is_active, 422, 'This product is no longer available.');
        $wishlist->items()->firstOrCreate(['product_id' => $product->id]);

        return WishlistResource::make($this->load($wishlist));
    }

    public function destroy(Request $request, string $productId): WishlistResource
    {
        $wishlist = $this->resolve($request);
        $product = Product::where('external_id', $productId)->firstOrFail();
        $wishlist->items()->where('product_id', $product->id)->delete();

        return WishlistResource::make($this->load($wishlist));
    }

    private function resolve(Request $request): Wishlist
    {
        if ($request->user('sanctum')) {
            return Wishlist::firstOrCreate(['user_id' => $request->user('sanctum')->id], ['guest_token' => $request->input('wishlist_token') ?? (string) Str::uuid()]);
        }

        return Wishlist::firstOrCreate(['guest_token' => $request->input('wishlist_token') ?? (string) Str::uuid()]);
    }

    private function load(Wishlist $wishlist): Wishlist
    {
        return $wishlist->load(['items.product.category', 'items.product.images', 'items.product.variants.size', 'items.product.variants.color']);
    }
}
