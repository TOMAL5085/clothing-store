<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function show(Request $request): CartResource
    {
        return CartResource::make($this->cartService->load($this->cartService->resolve($request->user(), $request->input('cart_token'))));
    }

    public function store(AddCartItemRequest $request): CartResource
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));

        return CartResource::make($this->cartService->add(
            $cart,
            $request->validated('product_id'),
            $request->validated('size'),
            (int) ($request->validated('quantity') ?? 1),
            $request->validated('color'),
        ));
    }

    public function update(UpdateCartItemRequest $request, string $item): CartResource
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));

        return CartResource::make($this->cartService->update($cart, $this->resolveItemId($cart, $item), (int) $request->validated('quantity')));
    }

    public function destroy(Request $request, string $item): CartResource
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));
        $cart->items()->whereKey($this->resolveItemId($cart, $item))->delete();

        return CartResource::make($this->cartService->load($cart));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));
        $cart->items()->delete();
        $cart->update(['promo_code' => null]);

        return response()->json(['message' => 'Cart cleared.']);
    }

    public function promo(Request $request): CartResource
    {
        $request->validate(['promo_code' => ['nullable', 'string', 'max:50'], 'cart_token' => ['nullable', 'uuid']]);
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));

        return CartResource::make($this->cartService->applyPromo($cart, $request->input('promo_code')));
    }

    private function resolveItemId($cart, string $item): int
    {
        if (ctype_digit($item)) {
            return (int) $item;
        }

        [$productId, $size] = array_pad(explode('__', $item, 2), 2, null);

        if (ctype_digit((string) $size)) {
            $line = $cart->items()
                ->where('product_variant_id', (int) $size)
                ->whereHas('product', fn ($query) => $query->where('external_id', $productId))
                ->firstOrFail();

            return $line->id;
        }

        $line = $cart->items()
            ->where('size', $size)
            ->whereHas('product', fn ($query) => $query->where('external_id', $productId))
            ->firstOrFail();

        return $line->id;
    }
}
