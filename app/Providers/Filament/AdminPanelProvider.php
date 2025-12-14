<?php

namespace App\Providers\Filament;

use App\Settings\GeneralSettings;
use Filament\Panel;
use Livewire\Livewire;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\BestSellingFoodChart;
use Resma\FilamentAwinTheme\FilamentAwinTheme;
use App\Filament\Widgets\BestSellingDrinkChart;
use App\Filament\Widgets\RevenueOverviewWidget;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Widgets\PeakHoursHeatmapWidget;
use App\Filament\Widgets\ReservationStatsWidget;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\DailyRevenueTrendWidget;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\Reservations\Widgets\ReservationCalendarWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = app(GeneralSettings::class);
        return $panel
            ->default()
            ->brandLogo($settings->app_logo ? Storage::url($settings->app_logo) : null)
            ->brandName($settings->app_name)
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                    // AccountWidget::class,
                    // FilamentInfoWidget::class,
                ReservationCalendarWidget::class,
                ReservationStatsWidget::class,
                RevenueOverviewWidget::class,
                BestSellingFoodChart::class,
                BestSellingDrinkChart::class,
                DailyRevenueTrendWidget::class,
                PeakHoursHeatmapWidget::class,
                LowStockAlertWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->maxContentWidth(Width::Full)
            ->plugins([
                FilamentApexChartsPlugin::make(),
                // FilamentAwinTheme::make()
                // ->primaryColor('#CF8B00'),
            ])
            ->navigationGroups([
                'Transaksi',
                'Produk',
                'Master Data',
                'Settings',
            ]);
    }
}
