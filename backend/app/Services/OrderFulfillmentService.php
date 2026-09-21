<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderFulfillmentService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function holdInventory(Order $order): void
    {
        if ($order->inventory_decremented_at) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);
            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if (! $product?->is_active || ! $product->in_stock || ! $variant?->is_active || $variant->stock_quantity < $item->quantity || $product->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages(['cart' => "{$item->product_name} is no longer available in the requested quantity."]);
            }

            $variant->decrement('stock_quantity', $item->quantity);
            $product->decrement('stock_quantity', $item->quantity);
            $product->refresh()->update(['in_stock' => $product->stock_quantity > 0]);
        }

        $order->update(['inventory_decremented_at' => now()]);
    }

    public function releaseInventory(Order $order): void
    {
        if (! $order->inventory_decremented_at) {
            return;
        }

        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);
            $product = Product::query()->lockForUpdate()->find($item->product_id);
            $variant?->increment('stock_quantity', $item->quantity);
            $product?->increment('stock_quantity', $item->quantity);
            $product?->refresh()->update(['in_stock' => $product->stock_quantity > 0]);
        }

        $order->update(['inventory_decremented_at' => null]);
    }

    public function markPaid(Order $order, Payment $payment, array $gatewayResult = []): void
    {
        if ($payment->status === 'paid' && $order->payment_status === 'paid') {
            return;
        }

        $oldStatus = $order->status;

        $this->holdInventory($order);

        $payment->fill([
            'status' => 'paid',
            'provider_event_id' => $gatewayResult['provider_event_id'] ?? $payment->provider_event_id,
            'method' => $gatewayResult['method'] ?? $payment->method,
            'amount_minor' => $gatewayResult['amount_minor'] ?? Money::toMinor($order->total),
            'payload' => array_merge($payment->payload ?? [], $gatewayResult['payload'] ?? []),
        ])->save();

        $nextStatus = $order->status === 'pending' ? 'processing' : $order->status;
        $order->update([
            'payment_status' => 'paid',
            'status' => $nextStatus,
        ]);

        $this->consumeCoupon($order);
        $this->clearCart($order);

        // Send notifications after transaction commits
        DB::afterCommit(function () use ($order, $oldStatus) {
            $this->notifications->orderPaid($order, $oldStatus);
        });
    }

    public function markFailed(Order $order, Payment $payment, string $reason, string $status = 'failed'): void
    {
        if ($payment->status === 'paid' || $order->payment_status === 'paid') {
            Log::info('payment.ignore_failure_after_paid', ['order' => $order->number]);

            return;
        }

        $payment->update([
            'status' => $status,
            'failure_reason' => $reason,
        ]);

        $order->update(['payment_status' => $status]);
        $this->releaseInventory($order);

        DB::afterCommit(function () use ($order, $reason) {
            $this->notifications->orderPaymentFailed($order, $reason);
        });
    }

    private function consumeCoupon(Order $order): void
    {
        if ($order->coupon_consumed_at || ! $order->promo_code) {
            return;
        }

        Coupon::query()->where('code', $order->promo_code)->increment('used_count');
        $order->update(['coupon_consumed_at' => now()]);
    }

    private function clearCart(Order $order): void
    {
        if ($order->cart_cleared_at) {
            return;
        }

        $cart = $order->cart_id ? Cart::query()->find($order->cart_id) : null;
        if ($cart) {
            $cart->items()->delete();
            $cart->update(['promo_code' => null]);
        }

        $order->update(['cart_cleared_at' => now()]);
    }
}
