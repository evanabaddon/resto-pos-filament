<?php

namespace App\Providers\Filament;

use App\Settings\GeneralSettings;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\BestSellingFoodChart;
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
use App\Filament\Widgets\AiDailySuggestionWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $settings = app(GeneralSettings::class);
            $logo = $settings->app_logo ? Storage::url($settings->app_logo) : null;
            $brandName = $settings->app_name ?? config('app.name');
        } catch (\Throwable $e) {
            $logo = null;
            $brandName = config('app.name');
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandLogo($logo)
            ->brandName($brandName)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->spa()
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s') // Balanced: Responsive but stable
            ->renderHook(
                'panels::body.end',
                fn() => view('filament.hooks.notification-sound')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AiDailySuggestionWidget::class,
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
                'Manajemen SDM',
                'Kemitraan (CRM)',
                'Laporan & Analisis',
                'AI Intelligence',
                'Super Chat',
                'Settings'
            ]);
    }
}
