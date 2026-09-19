<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const FREE_SHIPPING_THRESHOLD = 200.00;
    public const STANDARD_SHIPPING = 9.95;
    public const EXPRESS_SHIPPING = 18.00;

    public function resolve(?User $user, ?string $token = null): Cart
    {
        $query = Cart::query();

        if ($user) {
            return $query->firstOrCreate(['user_id' => $user->id], ['guest_token' => $token ?? (string) Str::uuid()]);
        }

        return $query->firstOrCreate(['guest_token' => $token ?? (string) Str::uuid()]);
    }

    public function add(Cart $cart, string $productExternalId, string $size, int $quantity = 1, ?string $color = null): Cart
    {
        $product = Product::query()->active()->where('external_id', $productExternalId)->firstOrFail();
        $variant = $this->variantFor($product, $size, $color);

        if (! $product->in_stock || $product->stock_quantity <= 0 || $variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Requested quantity is not available.']);
        }

        $item = $cart->items()->firstOrNew([
            'product_variant_id' => $variant->id,
        ]);

        $nextQty = min(($item->quantity ?: 0) + $quantity, 12);
        if ($variant->stock_quantity < $nextQty) {
            throw ValidationException::withMessages(['quantity' => 'Requested quantity exceeds available stock.']);
        }

        $item->fill([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'size' => $variant->size?->name ?? $size,
            'quantity' => $nextQty,
        ])->save();

        return $this->load($cart);
    }

    public function update(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $cart->items()->whereKey($itemId)->firstOrFail();

        if ($quantity === 0) {
            $item->delete();

            return $this->load($cart);
        }

        if (! $item->product->in_stock || ($item->variant?->stock_quantity ?? 0) < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Requested quantity exceeds available stock.']);
        }

        $item->update(['quantity' => min($quantity, 12)]);

        return $this->load($cart);
    }

    public function applyPromo(Cart $cart, ?string $code): Cart
    {
        $code = $code ? strtoupper(trim($code)) : null;

        if ($code !== null) {
            $coupon = Coupon::query()->where('code', $code)->first();
            if (! $coupon) {
                throw ValidationException::withMessages(['promo_code' => 'That promo code is not valid.']);
            }
            $coupon->validateFor($this->subtotal($cart), $cart->user);
        }

        $cart->update(['promo_code' => $code]);

        return $this->load($cart);
    }

    public function totals(Cart $cart, string $method = 'standard'): array
    {
        $cart = $this->load($cart);
        $subtotal = $this->subtotal($cart);
        $discount = $this->discount($cart, $subtotal);
        $shipping = $method === 'express' ? self::EXPRESS_SHIPPING : ($subtotal - $discount >= self::FREE_SHIPPING_THRESHOLD ? 0.0 : self::STANDARD_SHIPPING);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'shipping' => $subtotal > 0 ? $shipping : 0.0,
            'tax' => 0.0,
            'total' => round($subtotal - $discount + ($subtotal > 0 ? $shipping : 0.0), 2),
        ];
    }

    public function load(Cart $cart): Cart
    {
        return $cart->load(['items.product.category', 'items.product.images', 'items.product.variants.size', 'items.product.variants.color', 'items.variant']);
    }

    public function subtotal(Cart $cart): float
    {
        $cart = $this->load($cart);

        return round($cart->items->sum(fn ($item) => (float) ($item->variant?->price ?? $item->product->price) * $item->quantity), 2);
    }

    public function discount(Cart $cart, ?float $subtotal = null): float
    {
        if (! $cart->promo_code) {
            return 0.0;
        }

        $coupon = Coupon::query()->where('code', $cart->promo_code)->first();
        if (! $coupon) {
            return 0.0;
        }

        $subtotal ??= $this->subtotal($cart);
        try {
            $coupon->validateFor($subtotal, $cart->user);
        } catch (ValidationException) {
            return 0.0;
        }

        return $coupon->discountFor($subtotal);
    }

    public function coupon(Cart $cart): ?Coupon
    {
        return $cart->promo_code ? Coupon::query()->where('code', $cart->promo_code)->first() : null;
    }

    public function variantFor(Product $product, string $size, ?string $color = null): ProductVariant
    {
        $variant = $product->variants()
            ->with(['size', 'color'])
            ->whereHas('size', fn ($query) => $query->where('name', $size))
            ->when($color, fn ($query) => $query->whereHas('color', fn ($colorQuery) => $colorQuery->where('name', $color)))
            ->where('is_active', true)
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages(['size' => 'Selected variant is not available for this product.']);
        }

        return $variant;
    }
}
