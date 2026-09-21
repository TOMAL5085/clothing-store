<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\CustomerManagementController;
use App\Http\Controllers\Api\V1\Admin\CancellationManagementController;
use App\Http\Controllers\Api\V1\Admin\OrderManagementController;
use App\Http\Controllers\Api\V1\Admin\ProductManagementController;
use App\Http\Controllers\Api\V1\Admin\RefundManagementController;
use App\Http\Controllers\Api\V1\Admin\ReturnManagementController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderCancellationController;
use App\Http\Controllers\Api\V1\OrderReturnController;
use App\Http\Controllers\Api\V1\OrderTrackingController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SslCommerzController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [PasswordResetController::class, 'store']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/{product}/related', [ProductController::class, 'related']);

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{item}', [CartController::class, 'update']);
    Route::delete('cart/items/{item}', [CartController::class, 'destroy']);
    Route::delete('cart', [CartController::class, 'clear']);
    Route::post('cart/promo', [CartController::class, 'promo']);

    Route::get('wishlist', [WishlistController::class, 'show']);
    Route::post('wishlist/items', [WishlistController::class, 'store']);
    Route::delete('wishlist/items/{productId}', [WishlistController::class, 'destroy']);

    Route::post('checkout/quote', [CheckoutController::class, 'quote']);
    Route::post('checkout/orders', [CheckoutController::class, 'store']);
    Route::get('checkout/orders/{order}', [CheckoutController::class, 'show']);

    Route::post('payments/stripe/webhook', StripeWebhookController::class);
    Route::match(['get', 'post'], 'payments/sslcommerz/ipn', [SslCommerzController::class, 'ipn']);
    Route::match(['get', 'post'], 'payments/sslcommerz/success', [SslCommerzController::class, 'success']);
    Route::match(['get', 'post'], 'payments/sslcommerz/fail', [SslCommerzController::class, 'fail']);
    Route::match(['get', 'post'], 'payments/sslcommerz/cancel', [SslCommerzController::class, 'cancel']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/otp', [OtpController::class, 'store']);
        Route::post('auth/otp/verify', [OtpController::class, 'verify']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'password']);
        Route::apiResource('addresses', AddressController::class);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::get('orders/{order}/cancellation', [OrderCancellationController::class, 'show']);
        Route::post('orders/{order}/cancellation', [OrderCancellationController::class, 'store']);
        Route::get('orders/{order}/returns', [OrderReturnController::class, 'index']);
        Route::post('orders/{order}/returns', [OrderReturnController::class, 'store']);
        Route::get('orders/{order}/returns/{returnRequest}', [OrderReturnController::class, 'show']);

        // Tracking
        Route::get('orders/{order}/tracking', [OrderTrackingController::class, 'show']);

        Route::middleware('can:create,'.\App\Models\Product::class)->prefix('admin')->group(function () {
            Route::get('customers', [CustomerManagementController::class, 'index']);
            Route::get('customers/{customer}', [CustomerManagementController::class, 'show']);
            Route::patch('customers/{customer}/status', [CustomerManagementController::class, 'updateStatus']);
            Route::get('products', [ProductManagementController::class, 'index']);
            Route::post('products', [ProductManagementController::class, 'store']);
            Route::put('products/{product}', [ProductManagementController::class, 'update']);
            Route::delete('products/{product}', [ProductManagementController::class, 'destroy']);
            Route::post('products/{product}/inventory', [ProductManagementController::class, 'adjustInventory']);
            Route::get('orders', [OrderManagementController::class, 'index']);
            Route::get('orders/{order}', [OrderManagementController::class, 'show']);
            Route::patch('orders/{order}/status', [OrderManagementController::class, 'updateStatus']);
            Route::get('cancellations', [CancellationManagementController::class, 'index']);
            Route::patch('cancellations/{cancellationRequest}', [CancellationManagementController::class, 'update']);
            Route::get('returns', [ReturnManagementController::class, 'index']);
            Route::patch('returns/{returnRequest}', [ReturnManagementController::class, 'update']);
            Route::post('returns/{returnRequest}/received', [ReturnManagementController::class, 'received']);
            Route::get('refunds', [RefundManagementController::class, 'index']);
            Route::patch('refunds/{refund}', [RefundManagementController::class, 'update']);

            // Shipping
            Route::post('orders/{order}/shipment', [ShipmentController::class, 'store']);
            Route::get('orders/{order}/shipment', [ShipmentController::class, 'show']);
            Route::put('orders/{order}/shipment', [ShipmentController::class, 'update']);
            Route::get('orders/{order}/shipment/status', [ShipmentController::class, 'status']);
            Route::post('orders/{order}/shipment/status/sync', [ShipmentController::class, 'sync']);
        });
    });
});
