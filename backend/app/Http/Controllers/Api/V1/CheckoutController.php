<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateOrderRequest;
use App\Http\Requests\Checkout\QuoteCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    ) {}

    public function quote(QuoteCheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));
        $quote = $this->checkoutService->quote($cart, $request->validated(), $request->user());

        return response()->json(['data' => [
            'countryCode' => $quote['country_code'],
            'provider' => $quote['provider'],
            'intendedProvider' => $quote['intended_provider'],
            'paymentMode' => $quote['payment_mode'],
            'currency' => $quote['currency'],
            'method' => $quote['delivery_method'],
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount'],
            'shipping' => $quote['shipping'],
            'tax' => $quote['tax'],
            'total' => $quote['total'],
        ]]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request->user(), $request->input('cart_token'));
        $result = $this->checkoutService->placeOrder($cart, $request->validated(), $request->user());

        return OrderResource::make($result['order'])
            ->additional([
                'payment' => [
                    'mode' => config('payments.driver', 'demo') === 'demo' ? 'demo' : 'gateways',
                    'provider' => $result['order']->payment_provider,
                    'intendedProvider' => $result['intended_provider'],
                    'redirectUrl' => $result['redirect_url'],
                    'clientSecret' => $result['client_secret'],
                    'checkoutToken' => $result['order']->checkout_token,
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $public = $this->checkoutService->showPublic(
            $order,
            $request->query('checkout_token'),
            $request->user(),
        );

        $request->merge(['include_token' => false]);

        return OrderResource::make($public);
    }
}
