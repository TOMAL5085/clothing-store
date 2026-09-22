<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\SslCommerzGateway;
use App\Services\Payments\StripeGateway;
use App\Support\CountryResolver;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PaymentService $paymentService,
        private readonly OrderFulfillmentService $fulfillment,
        private readonly StripeGateway $stripe,
        private readonly SslCommerzGateway $sslcommerz,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function quote(Cart $cart, array $payload, ?User $user = null): array
    {
        $cart = $this->cartService->load($cart);
        $method = $payload['delivery_method'] ?? 'standard';
        $totals = $this->cartService->totals($cart, $method);
        $address = $this->resolveAddressPreview($payload, $user);
        $countryCode = CountryResolver::normalize($address['country']);
        $intended = CountryResolver::providerForCountryCode($countryCode);
        $demo = $this->isDemo();

        return [
            'country_code' => $countryCode,
            'provider' => $demo ? 'demo' : $intended,
            'intended_provider' => $intended,
            'payment_mode' => $demo ? 'demo' : 'gateways',
            'currency' => strtoupper((string) config('payments.store_currency', 'USD')),
            'delivery_method' => $method,
            ...$totals,
        ];
    }

    /**
     * @return array{order: Order, redirect_url:?string, client_secret:?string}
     */
    public function placeOrder(Cart $cart, array $payload, ?User $user = null): array
    {
        $cart = $this->cartService->load($cart);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your bag is empty.']);
        }

        $method = $payload['delivery_method'];
        $totals = $this->cartService->totals($cart, $method);
        $addressData = $this->resolveAddress($payload, $user);
        $countryCode = CountryResolver::normalize($addressData['country']);
        $intendedProvider = CountryResolver::providerForCountryCode($countryCode);
        $provider = $this->isDemo() ? 'demo' : $intendedProvider;
        $currency = strtoupper((string) config('payments.store_currency', 'USD'));

        if ($cart->promo_code) {
            $coupon = $this->cartService->coupon($cart);
            if (! $coupon) {
                throw ValidationException::withMessages(['promo_code' => 'That promo code is not valid.']);
            }
            $coupon->validateFor($totals['subtotal'], $user);
        }

        $charge = null;
        if ($this->isDemo()) {
            $charge = $this->paymentService->charge($payload['payment'] ?? [], $totals['total']);
        }

        return DB::transaction(function () use ($cart, $payload, $user, $method, $totals, $addressData, $countryCode, $provider, $intendedProvider, $currency, $charge) {
            $this->assertPurchasable($cart);

            $shipping = Address::create($this->addressAttributes($addressData, $user?->id));
            $billing = null;
            if (! empty($payload['billing_address_id']) || ! empty($payload['billing_address'])) {
                $billingData = $this->resolveAddress($payload, $user, 'billing');
                $billing = Address::create($this->addressAttributes($billingData, $user?->id));
            }

            $order = Order::create([
                'number' => $this->nextOrderNumber(),
                'user_id' => $user?->id,
                'anonymous_id' => $this->anonymousId($payload['anonymous_id'] ?? null),
                'cart_id' => $cart->id,
                'shipping_address_id' => $shipping->id,
                'billing_address_id' => $billing?->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'delivery_method' => $method,
                'country_code' => $countryCode,
                'payment_provider' => $provider,
                'currency' => $currency,
                'checkout_token' => (string) Str::uuid(),
                'promo_code' => $cart->promo_code,
                ...$totals,
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->variant;
                $unitPrice = (float) ($variant->price ?? $item->product->price);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_external_id' => $item->product->external_id,
                    'product_name' => $item->product->name,
                    'product_slug' => $item->product->slug,
                    'size' => $item->size,
                    'sku' => $variant?->sku ?? $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($unitPrice * $item->quantity, 2),
                ]);
            }

            $order->load(['items', 'shippingAddress', 'billingAddress']);

            $payment = $order->payment()->create([
                'provider' => $provider,
                'status' => 'pending',
                'reference' => $charge['reference'] ?? $provider.'_'.Str::lower(Str::random(24)),
                'amount' => $totals['total'],
                'amount_minor' => Money::toMinor($totals['total']),
                'currency' => $currency,
                'card_last_four' => $charge['card_last_four'] ?? null,
                'method' => $charge['method'] ?? null,
            ]);

            $redirect = null;
            $clientSecret = null;

            if ($provider === 'demo') {
                $this->fulfillment->markPaid($order, $payment, [
                    'method' => 'card',
                    'payload' => ['demo' => true],
                ]);
            } else {
                $initiation = $provider === CountryResolver::PROVIDER_SSLCOMMERZ
                    ? $this->sslcommerz->initiate($order->load('shippingAddress'), $payment)
                    : $this->stripe->initiate($order, $payment);
                $redirect = $initiation['redirect_url'];
                $clientSecret = $initiation['client_secret'];
            }

            // Send order placed notifications after transaction commits
            DB::afterCommit(function () use ($order) {
                $this->notifications->orderPlaced($order->fresh(['items', 'shippingAddress', 'billingAddress', 'payment', 'user']));
            });

            return [
                'order' => $order->fresh(['items', 'shippingAddress', 'billingAddress', 'payment']),
                'redirect_url' => $redirect,
                'client_secret' => $clientSecret,
                'intended_provider' => $intendedProvider,
            ];
        });
    }

    public function showPublic(Order $order, ?string $token, ?User $user = null): Order
    {
        $owns = $user && ($user->isAdmin() || $order->user_id === $user->id);
        $tokenMatch = $token && hash_equals((string) $order->checkout_token, $token);
        abort_unless($owns || $tokenMatch, 403);

        return $order->load(['items', 'shippingAddress', 'billingAddress', 'payment']);
    }

    private function isDemo(): bool
    {
        return config('payments.driver', 'demo') === 'demo';
    }

    /**
     * Accept the browser's anonymous measurement identity for attribution
     * linking only. Malformed values are dropped, never stored.
     */
    private function anonymousId(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = trim(mb_substr($value, 0, 64));

        return preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $value) ? $value : null;
    }

    private function assertPurchasable(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $variant = $item->variant()->lockForUpdate()->first();
            $product = $item->product()->lockForUpdate()->first();
            if (
                ! $product?->is_active
                || ! $product->in_stock
                || ! $variant?->is_active
                || $variant->stock_quantity < $item->quantity
                || $product->stock_quantity < $item->quantity
            ) {
                throw ValidationException::withMessages(['cart' => "{$item->product->name} is no longer available in the requested quantity."]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveAddress(array $payload, ?User $user, string $kind = 'shipping'): array
    {
        $idKey = $kind.'_address_id';
        $dataKey = $kind.'_address';

        if (! empty($payload[$idKey])) {
            if (! $user) {
                throw ValidationException::withMessages([$idKey => 'Saved addresses can only be used while signed in.']);
            }

            $address = Address::query()->whereKey($payload[$idKey])->first();
            if (! $address || $address->user_id !== $user->id) {
                throw ValidationException::withMessages([$idKey => 'The selected address was not found.']);
            }

            return [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'email' => $address->email,
                'phone' => $address->phone,
                'address' => $address->address,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
            ];
        }

        $input = $payload[$dataKey] ?? null;
        if (! is_array($input)) {
            throw ValidationException::withMessages([$dataKey => 'Shipping details are required.']);
        }

        return [
            'first_name' => $input['firstName'] ?? $input['first_name'] ?? '',
            'last_name' => $input['lastName'] ?? $input['last_name'] ?? '',
            'email' => $input['email'] ?? '',
            'phone' => $input['phone'] ?? null,
            'address' => $input['address'] ?? '',
            'city' => $input['city'] ?? '',
            'postal_code' => $input['postalCode'] ?? $input['postal_code'] ?? '',
            'country' => $input['country'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{country: string}
     */
    private function resolveAddressPreview(array $payload, ?User $user): array
    {
        if (! empty($payload['shipping_address_id']) || ! empty($payload['shipping_address'])) {
            return $this->resolveAddress($payload, $user);
        }

        throw ValidationException::withMessages(['shipping_address.country' => 'A valid shipping country is required.']);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function addressAttributes(array $address, ?int $userId): array
    {
        return [
            'user_id' => $userId,
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'email' => $address['email'],
            'phone' => $address['phone'] ?? null,
            'address' => $address['address'],
            'city' => $address['city'],
            'postal_code' => $address['postal_code'],
            'country' => $address['country'],
        ];
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'JAAJ-'.random_int(100000, 999999);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
