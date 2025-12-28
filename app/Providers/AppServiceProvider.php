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

        // DISABLED: SaleItem Observer causes double stock deduction
        // Stock is already counted in draft sales by RecipeStockChecker
        // Actual deduction happens via purchase system
        // \App\Models\SaleItem::observe(\App\Observers\SaleItemObserver::class);
    }
}
