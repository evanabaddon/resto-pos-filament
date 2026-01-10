<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverviewWidget extends StatsOverviewWidget
{
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    protected function getStats(): array
    {
        // Cache for 5 minutes (data needs to be relatively fresh)
        // Cache for 5 minutes (data needs to be relatively fresh), specific to locale
        return Cache::remember('revenue_overview_stats_' . app()->getLocale(), 300, function () {
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();

            // Pendapatan hari ini
            $todayRevenue = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('final_total');

            // Pendapatan kemarin
            $yesterdayRevenue = Sale::whereDate('created_at', $yesterday)
                ->where('status', 'completed')
                ->sum('final_total');

            // Perubahan persentase
            $revenueChange = $yesterdayRevenue > 0
                ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100
                : 0;

            // Rata-rata nilai transaksi
            $avgTransaction = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->avg('final_total');

            // Total transaksi hari ini
            $transactionCount = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->count();

            return [
                Stat::make(__('messages.today_revenue'), 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                    ->description($revenueChange > 0 ? "↑ " . number_format($revenueChange, 1) . __('messages.from_yesterday') : "↓ " . number_format(abs($revenueChange), 1) . __('messages.from_yesterday'))
                    ->descriptionIcon($revenueChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($revenueChange > 0 ? 'success' : 'danger'),

                Stat::make(__('messages.total_transactions'), $transactionCount)
                    ->description(__('messages.average_value', ['value' => number_format($avgTransaction ?? 0, 0, ',', '.')]))
                    ->descriptionIcon('heroicon-m-shopping-cart')
                    ->color('info'),

                Stat::make(__('messages.popular_payment'), $this->getPopularPaymentMethod())
                    ->description(__('messages.most_frequent'))
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color('warning'),
            ];
        });
    }

    protected function getPopularPaymentMethod(): string
    {
        $popular = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->with('paymentMethod')
            ->select('payment_method_id', DB::raw('count(*) as total'))
            ->groupBy('payment_method_id')
            ->orderByDesc('total')
            ->first();

        // Jika menggunakan payment_method_id dengan relasi
        if ($popular && $popular->paymentMethod) {
            return $popular->paymentMethod->name; // Asumsi kolom 'name' di tabel payment_methods
        }

        // Fallback ke payment_method lama jika masih ada
        if ($popular && $popular->payment_method) {
            return $popular->payment_method;
        }

        return '-';
    }
}
