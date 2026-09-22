<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\CancellationManagementController;
use App\Http\Controllers\Api\V1\Admin\CustomerManagementController;
use App\Http\Controllers\Api\V1\Admin\OrderManagementController;
use App\Http\Controllers\Api\V1\Admin\ProductManagementController;
use App\Http\Controllers\Api\V1\Admin\RefundManagementController;
use App\Http\Controllers\Api\V1\Admin\ReturnManagementController;
use App\Http\Controllers\Api\V1\Admin\ReviewManagementController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\Notifications\AdminNotificationController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\OrderCancellationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderReturnController;
use App\Http\Controllers\Api\V1\OrderTrackingController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductReviewController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SslCommerzController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-reset');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}/reviews', [ProductReviewController::class, 'index']);
    Route::get('products/{product}/reviews/summary', [ProductReviewController::class, 'summary']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/{product}/related', [ProductController::class, 'related']);

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store'])->middleware('throttle:storefront-mutations');
    Route::patch('cart/items/{item}', [CartController::class, 'update'])->middleware('throttle:storefront-mutations');
    Route::delete('cart/items/{item}', [CartController::class, 'destroy'])->middleware('throttle:storefront-mutations');
    Route::delete('cart', [CartController::class, 'clear'])->middleware('throttle:storefront-mutations');
    Route::post('cart/promo', [CartController::class, 'promo'])->middleware('throttle:promo');

    Route::get('wishlist', [WishlistController::class, 'show']);
    Route::post('wishlist/items', [WishlistController::class, 'store'])->middleware('throttle:storefront-mutations');
    Route::delete('wishlist/items/{productId}', [WishlistController::class, 'destroy'])->middleware('throttle:storefront-mutations');

    Route::post('checkout/quote', [CheckoutController::class, 'quote'])->middleware('throttle:checkout-quote');
    Route::post('checkout/orders', [CheckoutController::class, 'store'])->middleware('throttle:checkout');
    Route::get('checkout/orders/{order}', [CheckoutController::class, 'show'])->middleware('throttle:order-lookup');

    Route::post('payments/stripe/webhook', StripeWebhookController::class);
    Route::match(['get', 'post'], 'payments/sslcommerz/ipn', [SslCommerzController::class, 'ipn']);
    Route::match(['get', 'post'], 'payments/sslcommerz/success', [SslCommerzController::class, 'success']);
    Route::match(['get', 'post'], 'payments/sslcommerz/fail', [SslCommerzController::class, 'fail']);
    Route::match(['get', 'post'], 'payments/sslcommerz/cancel', [SslCommerzController::class, 'cancel']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/otp', [OtpController::class, 'store'])->middleware('throttle:otp');
        Route::post('auth/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp');
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'password']);
        Route::apiResource('addresses', AddressController::class);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::get('orders/{order}/cancellation', [OrderCancellationController::class, 'show']);
        Route::post('orders/{order}/cancellation', [OrderCancellationController::class, 'store'])->middleware('throttle:resolution-requests');
        Route::get('orders/{order}/returns', [OrderReturnController::class, 'index']);
        Route::post('orders/{order}/returns', [OrderReturnController::class, 'store'])->middleware('throttle:resolution-requests');
        Route::get('orders/{order}/returns/{returnRequest}', [OrderReturnController::class, 'show']);

        // Tracking
        Route::get('orders/{order}/tracking', [OrderTrackingController::class, 'show']);

        // Product reviews
        Route::get('products/{product}/reviews/mine', [ProductReviewController::class, 'mine']);
        Route::get('products/{product}/reviews/eligibility', [ProductReviewController::class, 'eligibility']);
        Route::post('products/{product}/reviews', [ProductReviewController::class, 'store'])->middleware('throttle:reviews');
        Route::match(['put', 'patch'], 'reviews/{review}', [ProductReviewController::class, 'update'])->middleware('throttle:reviews');
        Route::delete('reviews/{review}', [ProductReviewController::class, 'destroy'])->middleware('throttle:reviews');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

        // Notification preferences (caller's own rows only)
        Route::get('notification-preferences', [NotificationPreferenceController::class, 'index']);
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update']);

        Route::middleware('can:create,'.Product::class)->prefix('admin')->group(function () {
            Route::get('customers', [CustomerManagementController::class, 'index']);
            Route::get('customers/{customer}', [CustomerManagementController::class, 'show']);
            Route::patch('customers/{customer}/status', [CustomerManagementController::class, 'updateStatus'])->middleware('throttle:admin-mutations');
            Route::get('products', [ProductManagementController::class, 'index']);
            Route::post('products', [ProductManagementController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::put('products/{product}', [ProductManagementController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('products/{product}', [ProductManagementController::class, 'destroy'])->middleware('throttle:admin-mutations');
            Route::post('products/{product}/inventory', [ProductManagementController::class, 'adjustInventory'])->middleware('throttle:admin-mutations');
            Route::get('orders', [OrderManagementController::class, 'index']);
            Route::get('orders/{order}', [OrderManagementController::class, 'show']);
            Route::patch('orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->middleware('throttle:admin-mutations');
            Route::get('cancellations', [CancellationManagementController::class, 'index']);
            Route::patch('cancellations/{cancellationRequest}', [CancellationManagementController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::get('returns', [ReturnManagementController::class, 'index']);
            Route::patch('returns/{returnRequest}', [ReturnManagementController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::post('returns/{returnRequest}/received', [ReturnManagementController::class, 'received'])->middleware('throttle:admin-mutations');
            Route::get('refunds', [RefundManagementController::class, 'index']);
            Route::patch('refunds/{refund}', [RefundManagementController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::get('reviews', [ReviewManagementController::class, 'index']);
            Route::get('reviews/{review}', [ReviewManagementController::class, 'show']);
            Route::patch('reviews/{review}', [ReviewManagementController::class, 'update'])->middleware('throttle:admin-mutations');

            // Audit log
            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::get('audit-logs/actions', [AuditLogController::class, 'actions']);

            // Analytics
            Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
            Route::get('analytics/sales', [AnalyticsController::class, 'sales']);
            Route::get('analytics/orders', [AnalyticsController::class, 'orders']);
            Route::get('analytics/products', [AnalyticsController::class, 'products']);
            Route::get('analytics/categories', [AnalyticsController::class, 'categories']);
            Route::get('analytics/customers', [AnalyticsController::class, 'customers']);
            Route::get('analytics/inventory', [AnalyticsController::class, 'inventory']);
            Route::get('analytics/reviews', [AnalyticsController::class, 'reviews']);
            Route::get('analytics/wishlist', [AnalyticsController::class, 'wishlist']);

            // Shipping
            Route::post('orders/{order}/shipment', [ShipmentController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('orders/{order}/shipment', [ShipmentController::class, 'show']);
            Route::put('orders/{order}/shipment', [ShipmentController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::get('orders/{order}/shipment/status', [ShipmentController::class, 'status']);
            Route::post('orders/{order}/shipment/status/sync', [ShipmentController::class, 'sync'])->middleware('throttle:admin-mutations');

            // Admin Notifications
            Route::get('notifications', [AdminNotificationController::class, 'index']);
            Route::get('notifications/unread-count', [AdminNotificationController::class, 'unreadCount']);
            Route::post('notifications/{id}/read', [AdminNotificationController::class, 'markAsRead']);
            Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead']);
        });
    });
});
