<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverviewWidget extends StatsOverviewWidget
{    
    protected function getStats(): array
    {
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
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description($revenueChange > 0 ? "↑ " . number_format($revenueChange, 1) . '% dari kemarin' : "↓ " . number_format(abs($revenueChange), 1) . '% dari kemarin')
                ->descriptionIcon($revenueChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange > 0 ? 'success' : 'danger'),
                
            Stat::make('Total Transaksi', $transactionCount)
                ->description('Rata-rata: Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
                
            Stat::make('Metode Pembayaran Terpopuler', $this->getPopularPaymentMethod())
                ->description('Paling banyak digunakan hari ini')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),
        ];
    }

    protected function getPopularPaymentMethod(): string
    {
        $popular = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->first();
            
        return $popular ? $popular->payment_method : '-';
    }
}
