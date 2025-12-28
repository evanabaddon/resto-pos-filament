<?php

namespace App\Providers;


use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\UnitConversionService::class, function ($app) {
            return new \App\Services\UnitConversionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register SaleItem Observer for prepared stock deduction
        \App\Models\SaleItem::observe(\App\Observers\SaleItemObserver::class);
    }
}
