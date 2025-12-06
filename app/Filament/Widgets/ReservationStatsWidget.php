<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Reservation::whereDate('reservation_date', today())->count();
        $pending = Reservation::where('status', 'pending')->count();
        $confirmed = Reservation::where('status', 'confirmed')->count();
        
        return [
            Stat::make('Reservasi Hari Ini', $today)
                ->description('Total booking hari ini')
                ->descriptionIcon('heroicon-o-calendar')
                ->color($today > 0 ? 'success' : 'gray'),
                
            Stat::make('Menunggu Konfirmasi', $pending)
                ->description('Perlu tindakan')
                ->descriptionIcon($pending > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($pending > 0 ? 'warning' : 'success'),
                
            Stat::make('Sudah Dikonfirmasi', $confirmed)
                ->description('Siap dilayani')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('info'),
        ];
    }
}
