<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReservationStatsWidget extends StatsOverviewWidget
{
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    protected function getStats(): array
    {
        // Cache for 5 minutes
        return Cache::remember('reservation_stats', 300, function () {
            $today = Reservation::whereDate('reservation_date', today())->count();
            $pending = Reservation::where('status', 'pending')->count();
            $confirmed = Reservation::where('status', 'confirmed')->count();

            return [
                Stat::make(__('messages.today_reservations'), $today)
                    ->description(__('messages.today_booking_total'))
                    ->descriptionIcon('heroicon-o-calendar')
                    ->color($today > 0 ? 'success' : 'gray'),

                Stat::make(__('messages.waiting_confirmation'), $pending)
                    ->description(__('messages.needs_action'))
                    ->descriptionIcon($pending > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    ->color($pending > 0 ? 'warning' : 'success'),

                Stat::make(__('messages.confirmed'), $confirmed)
                    ->description(__('messages.ready_to_serve'))
                    ->descriptionIcon('heroicon-o-check-badge')
                    ->color('info'),
            ];
        });
    }
}
