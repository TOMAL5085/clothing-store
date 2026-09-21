<?php

namespace App\Providers;

use App\Services\Couriers\CourierGateway;
use App\Services\Couriers\CourierService;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CourierGateway::class, function () {
            $default = config('couriers.default', 'mock');
            return app(config("couriers.providers.{$default}.class", \App\Services\Couriers\MockCourierGateway::class));
        });

        $this->app->singleton(CourierService::class, function ($app) {
            return new CourierService($app->make(CourierGateway::class));
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}