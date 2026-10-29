<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\V1\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\CancellationManagementController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CityController as AdminCityController;
use App\Http\Controllers\Api\V1\Admin\CmsContentController as AdminCmsContentController;
use App\Http\Controllers\Api\V1\Admin\CustomerManagementController;
use App\Http\Controllers\Api\V1\Admin\EmployeeSalaryController as AdminEmployeeSalaryController;
use App\Http\Controllers\Api\V1\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Api\V1\Admin\ManualPaymentController as AdminManualPaymentController;
use App\Http\Controllers\Api\V1\Admin\OrderManagementController;
use App\Http\Controllers\Api\V1\Admin\ProductManagementController;
use App\Http\Controllers\Api\V1\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Api\V1\Admin\RefundManagementController;
use App\Http\Controllers\Api\V1\Admin\ReturnManagementController;
use App\Http\Controllers\Api\V1\Admin\ReviewManagementController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController;
use App\Http\Controllers\Api\V1\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Api\V1\Admin\SupplierLedgerController as AdminSupplierLedgerController;
use App\Http\Controllers\Api\V1\Admin\ZoneController as AdminZoneController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\CmsContentController;
use App\Http\Controllers\Api\V1\EmployeeSalaryController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ManualPaymentController;
use App\Http\Controllers\Api\V1\PhaseOperationsController;
use App\Http\Controllers\Api\V1\Admin\PhaseOperationsController as AdminPhaseOperationsController;
use App\Http\Controllers\Api\V1\MarketingConsentController;
use App\Http\Controllers\Api\V1\MarketingEventController;
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
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-reset');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);
    Route::get('zones', [ZoneController::class, 'index']);
    Route::get('zones/{zone}', [ZoneController::class, 'show']);
    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{city}', [CityController::class, 'show']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::get('accounts', [AccountController::class, 'index']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::get('expenses/{expense}', [ExpenseController::class, 'show']);
    Route::get('salaries', [EmployeeSalaryController::class, 'index']);
    Route::get('salaries/{salary}', [EmployeeSalaryController::class, 'show']);
    Route::get('manual-payments', [ManualPaymentController::class, 'index']);
    Route::get('manual-payments/{payment}', [ManualPaymentController::class, 'show']);
    Route::post('visitors', [PhaseOperationsController::class, 'visitor'])->middleware('throttle:marketing-events');
    Route::get('visitor-summary', [PhaseOperationsController::class, 'visitorSummary']);
    Route::get('store-settings', [PhaseOperationsController::class, 'settings']);
    Route::post('weight-price/quote', [PhaseOperationsController::class, 'weightQuote']);
    Route::get('cms/content', [CmsContentController::class, 'index']);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/{product}/reviews', [ProductReviewController::class, 'index']);
    Route::get('products/{product}/reviews/summary', [ProductReviewController::class, 'summary']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/{product}/related', [ProductController::class, 'related']);

    Route::get('cart', [CartController::class, 'show']);
    Route::get('cart/recover', [CartController::class, 'recover']);
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

    Route::post('marketing/events', [MarketingEventController::class, 'store'])->middleware('throttle:marketing-events');

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
        Route::get('profile/marketing-consent', [MarketingConsentController::class, 'show']);
        Route::put('profile/marketing-consent', [MarketingConsentController::class, 'update']);
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

            // Brands
            Route::get('brands', [AdminBrandController::class, 'index']);
            Route::post('brands', [AdminBrandController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('brands/{brand}', [AdminBrandController::class, 'show']);
            Route::match(['put', 'patch'], 'brands/{brand}', [AdminBrandController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('brands/{brand}', [AdminBrandController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Categories (incl. sub-categories)
            Route::get('categories', [AdminCategoryController::class, 'index']);
            Route::post('categories', [AdminCategoryController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('categories/{category}', [AdminCategoryController::class, 'show']);
            Route::match(['put', 'patch'], 'categories/{category}', [AdminCategoryController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('throttle:admin-mutations');

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

            // CMS content
            Route::get('cms/content', [AdminCmsContentController::class, 'index']);
            Route::post('cms/content', [AdminCmsContentController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('cms/content/{content}', [AdminCmsContentController::class, 'show']);
            Route::match(['put', 'patch'], 'cms/content/{content}', [AdminCmsContentController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('cms/content/{content}', [AdminCmsContentController::class, 'destroy'])->middleware('throttle:admin-mutations');
            Route::post('cms/content/{content}/image', [AdminCmsContentController::class, 'uploadImage'])->middleware('throttle:admin-mutations');

            // Banners
            Route::get('banners', [AdminBannerController::class, 'index']);
            Route::post('banners', [AdminBannerController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('banners/{banner}', [AdminBannerController::class, 'show']);
            Route::match(['put', 'patch'], 'banners/{banner}', [AdminBannerController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('banners/{banner}', [AdminBannerController::class, 'destroy'])->middleware('throttle:admin-mutations');
            Route::post('banners/{banner}/image', [AdminBannerController::class, 'uploadImage'])->middleware('throttle:admin-mutations');

            // Zones
            Route::get('zones', [AdminZoneController::class, 'index']);
            Route::post('zones', [AdminZoneController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('zones/{zone}', [AdminZoneController::class, 'show']);
            Route::match(['put', 'patch'], 'zones/{zone}', [AdminZoneController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('zones/{zone}', [AdminZoneController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Cities
            Route::get('cities', [AdminCityController::class, 'index']);
            Route::post('cities', [AdminCityController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('cities/{city}', [AdminCityController::class, 'show']);
            Route::match(['put', 'patch'], 'cities/{city}', [AdminCityController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('cities/{city}', [AdminCityController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Suppliers
            Route::get('suppliers', [AdminSupplierController::class, 'index']);
            Route::post('suppliers', [AdminSupplierController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('suppliers/{supplier}', [AdminSupplierController::class, 'show']);
            Route::match(['put', 'patch'], 'suppliers/{supplier}', [AdminSupplierController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('suppliers/{supplier}', [AdminSupplierController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Accounts
            Route::get('accounts', [AdminAccountController::class, 'index']);
            Route::post('accounts', [AdminAccountController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('accounts/{account}', [AdminAccountController::class, 'show']);
            Route::match(['put', 'patch'], 'accounts/{account}', [AdminAccountController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('accounts/{account}', [AdminAccountController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Expenses
            Route::get('expenses', [AdminExpenseController::class, 'index']);
            Route::post('expenses', [AdminExpenseController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('expenses/{expense}', [AdminExpenseController::class, 'show']);
            Route::match(['put', 'patch'], 'expenses/{expense}', [AdminExpenseController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('expenses/{expense}', [AdminExpenseController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Salaries
            Route::get('salaries', [AdminEmployeeSalaryController::class, 'index']);
            Route::post('salaries', [AdminEmployeeSalaryController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('salaries/{salary}', [AdminEmployeeSalaryController::class, 'show']);
            Route::match(['put', 'patch'], 'salaries/{salary}', [AdminEmployeeSalaryController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('salaries/{salary}', [AdminEmployeeSalaryController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Manual Payments
            Route::get('manual-payments', [AdminManualPaymentController::class, 'index']);
            Route::post('manual-payments', [AdminManualPaymentController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('manual-payments/{payment}', [AdminManualPaymentController::class, 'show']);
            Route::match(['put', 'patch'], 'manual-payments/{payment}', [AdminManualPaymentController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('manual-payments/{payment}', [AdminManualPaymentController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Phases C-G: wholesale, weight pricing, order workflow, reports, courier, fraud, settings
            Route::get('wholesale-customers', [AdminPhaseOperationsController::class, 'wholesaleCustomers']);
            Route::post('wholesale-customers', [AdminPhaseOperationsController::class, 'storeWholesaleCustomer'])->middleware('throttle:admin-mutations');
            Route::patch('wholesale-customers/{customer}', [AdminPhaseOperationsController::class, 'updateWholesaleCustomer'])->middleware('throttle:admin-mutations');
            Route::delete('wholesale-customers/{customer}', [AdminPhaseOperationsController::class, 'deleteWholesaleCustomer'])->middleware('throttle:admin-mutations');
            Route::post('orders/{order}/wholesale', [AdminPhaseOperationsController::class, 'markWholesaleOrder'])->middleware('throttle:admin-mutations');
            Route::get('wholesale-customers/{customer}/ledger', [AdminPhaseOperationsController::class, 'wholesaleLedger']);
            Route::post('wholesale-customers/{customer}/ledger', [AdminPhaseOperationsController::class, 'addWholesaleLedger'])->middleware('throttle:admin-mutations');
            Route::get('weight-price/rules', [AdminPhaseOperationsController::class, 'weightRules']);
            Route::post('weight-price/rules', [AdminPhaseOperationsController::class, 'storeWeightRule'])->middleware('throttle:admin-mutations');
            Route::delete('weight-price/rules/{rule}', [AdminPhaseOperationsController::class, 'deleteWeightRule'])->middleware('throttle:admin-mutations');
            Route::get('order-assignments', [AdminPhaseOperationsController::class, 'assignments']);
            Route::get('reports/staff-orders', [AdminPhaseOperationsController::class, 'staffOrderReport']);
            Route::post('orders/{order}/assign', [AdminPhaseOperationsController::class, 'assignOrder'])->middleware('throttle:admin-mutations');
            Route::post('orders/{order}/disburse', [AdminPhaseOperationsController::class, 'disburseOrder'])->middleware('throttle:admin-mutations');
            Route::post('orders/auto-disburse', [AdminPhaseOperationsController::class, 'autoDisburse'])->middleware('throttle:admin-mutations');
            Route::get('reports/orders', [AdminPhaseOperationsController::class, 'orderReport']);
            Route::get('reports/orders/download', [AdminPhaseOperationsController::class, 'downloadOrders']);
            Route::post('courier/bulk', [AdminPhaseOperationsController::class, 'courierBulk'])->middleware('throttle:admin-mutations');
            Route::post('courier/bulk-import', [AdminPhaseOperationsController::class, 'courierBulkImport'])->middleware('throttle:admin-mutations');
            Route::post('courier/auto-entry', [AdminPhaseOperationsController::class, 'courierAutoEntry'])->middleware('throttle:admin-mutations');
            Route::get('courier/reports', [AdminPhaseOperationsController::class, 'courierReport']);
            Route::get('orders/{order}/invoice', [AdminPhaseOperationsController::class, 'invoice']);
            Route::post('orders/{order}/invoice/email', [AdminPhaseOperationsController::class, 'emailInvoice'])->middleware('throttle:admin-mutations');
            Route::post('orders/{order}/fraud-check', [AdminPhaseOperationsController::class, 'fraudCheck'])->middleware('throttle:admin-mutations');
            Route::get('abandoned-carts', [AdminPhaseOperationsController::class, 'abandonedCarts']);
            Route::post('abandoned-carts/{cart}/recover', [AdminPhaseOperationsController::class, 'recoverCart'])->middleware('throttle:admin-mutations');
            Route::get('visitor-summary', [PhaseOperationsController::class, 'visitorSummary']);
            Route::get('store-settings', [AdminPhaseOperationsController::class, 'settings']);
            Route::put('store-settings', [AdminPhaseOperationsController::class, 'saveSetting'])->middleware('throttle:admin-mutations');
            Route::get('marketing/config', [AdminPhaseOperationsController::class, 'marketingConfig']);
            Route::get('marketing/fraud-report', [AdminPhaseOperationsController::class, 'fraudReport']);
            Route::get('security/blocked-ips', [AdminPhaseOperationsController::class, 'blockedIps']);
            Route::post('security/blocked-ips', [AdminPhaseOperationsController::class, 'blockIp'])->middleware('throttle:admin-mutations');
            Route::delete('security/blocked-ips/{blockedIp}', [AdminPhaseOperationsController::class, 'unblockIp'])->middleware('throttle:admin-mutations');

            // Purchases
            Route::get('purchases', [AdminPurchaseController::class, 'index']);
            Route::post('purchases', [AdminPurchaseController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::get('purchases/{purchase}', [AdminPurchaseController::class, 'show']);
            Route::match(['put', 'patch'], 'purchases/{purchase}', [AdminPurchaseController::class, 'update'])->middleware('throttle:admin-mutations');
            Route::delete('purchases/{purchase}', [AdminPurchaseController::class, 'destroy'])->middleware('throttle:admin-mutations');

            // Supplier Ledger
            Route::get('supplier-ledger', [AdminSupplierLedgerController::class, 'index']);
            Route::get('supplier-ledger/{ledger}', [AdminSupplierLedgerController::class, 'show']);
            Route::post('supplier-ledger', [AdminSupplierLedgerController::class, 'store'])->middleware('throttle:admin-mutations');
            Route::delete('supplier-ledger/{ledger}', [AdminSupplierLedgerController::class, 'destroy'])->middleware('throttle:admin-mutations');

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
            Route::get('analytics/marketing', [AnalyticsController::class, 'marketing']);

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
