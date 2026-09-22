<?php

namespace App\Providers;

use App\Services\Couriers\CourierGateway;
use App\Services\Couriers\CourierService;
use App\Services\Couriers\MockCourierGateway;
use App\Services\NotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CourierGateway::class, function () {
            $default = config('couriers.default', 'mock');

            return app(config("couriers.providers.{$default}.class", MockCourierGateway::class));
        });

        $this->app->singleton(CourierService::class, function ($app) {
            return new CourierService($app->make(CourierGateway::class));
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerBroadcasting();
    }

    /**
     * Private notification channels plus the Sanctum-guarded socket auth
     * route. Guests never reach the channel callbacks.
     */
    private function registerBroadcasting(): void
    {
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        require base_path('routes/channels.php');
    }

    /**
     * Named API rate limiters (see config/security.php for thresholds).
     *
     * Authenticated callers are segmented by user ID and guests by IP so a
     * single abusive client cannot block ordinary shared-network users.
     * Secrets are never used as limiter keys. Webhook and payment-callback
     * routes are deliberately excluded because providers retry delivery.
     */
    private function registerRateLimiters(): void
    {
        $limits = fn (string $name): array => config("security.rate_limits.{$name}", []);
        $actor = fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

        RateLimiter::for('login', function (Request $request) use ($limits) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute($limits('login')['per_minute'] ?? 5)->by($key),
                Limit::perHour($limits('login')['per_hour'] ?? 30)->by($key),
            ];
        });

        RateLimiter::for('register', function (Request $request) use ($limits) {
            return [
                Limit::perMinute($limits('register')['per_minute'] ?? 3)->by($request->ip()),
                Limit::perHour($limits('register')['per_hour'] ?? 10)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) use ($limits) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute($limits('password_reset')['per_minute'] ?? 3)->by($key),
                Limit::perHour($limits('password_reset')['per_hour'] ?? 10)->by($key),
            ];
        });

        RateLimiter::for('otp', function (Request $request) use ($limits, $actor) {
            return Limit::perMinute($limits('otp')['per_minute'] ?? 10)->by($actor($request));
        });

        RateLimiter::for('order-lookup', function (Request $request) use ($limits, $actor) {
            return Limit::perMinute($limits('order_lookup')['per_minute'] ?? 20)->by($actor($request));
        });

        RateLimiter::for('checkout-quote', function (Request $request) use ($limits, $actor) {
            return Limit::perMinute($limits('checkout_quote')['per_minute'] ?? 30)->by($actor($request));
        });

        RateLimiter::for('checkout', function (Request $request) use ($limits, $actor) {
            $key = $actor($request);

            return [
                Limit::perMinute($limits('checkout')['per_minute'] ?? 6)->by($key),
                Limit::perHour($limits('checkout')['per_hour'] ?? 60)->by($key),
            ];
        });

        RateLimiter::for('promo', function (Request $request) use ($limits, $actor) {
            $key = $actor($request);

            return [
                Limit::perMinute($limits('promo')['per_minute'] ?? 10)->by($key),
                Limit::perHour($limits('promo')['per_hour'] ?? 100)->by($key),
            ];
        });

        RateLimiter::for('reviews', function (Request $request) use ($limits) {
            $key = (string) $request->user()?->getAuthIdentifier();

            return [
                Limit::perMinute($limits('reviews')['per_minute'] ?? 10)->by($key),
                Limit::perHour($limits('reviews')['per_hour'] ?? 100)->by($key),
            ];
        });

        RateLimiter::for('storefront-mutations', function (Request $request) use ($limits, $actor) {
            return Limit::perMinute($limits('storefront_mutations')['per_minute'] ?? 60)->by($actor($request));
        });

        RateLimiter::for('resolution-requests', function (Request $request) use ($limits) {
            $key = (string) $request->user()?->getAuthIdentifier();

            return [
                Limit::perMinute($limits('resolution_requests')['per_minute'] ?? 10)->by($key),
                Limit::perHour($limits('resolution_requests')['per_hour'] ?? 60)->by($key),
            ];
        });

        RateLimiter::for('admin-mutations', function (Request $request) use ($limits) {
            return Limit::perMinute($limits('admin_mutations')['per_minute'] ?? 120)
                ->by((string) $request->user()?->getAuthIdentifier());
        });
    }
}
