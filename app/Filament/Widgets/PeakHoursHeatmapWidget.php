<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PeakHoursHeatmapWidget extends Widget
{
    protected string $view = 'filament.widgets.peak-hours-heatmap-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;
    
    public array $heatmapData = [];
    public int $maxTransactions = 1;

    public function mount()
    {
        $this->heatmapData = $this->getHeatmapData();
        $this->maxTransactions = $this->getMaxTransactions();
    }
    
    protected function getHeatmapData(): array
    {
        $data = Sale::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('DAYNAME(created_at) as day'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(final_total) as avg_value')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('hour', 'day')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('hour')
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        // Sesuaikan dengan jam operasional resto Anda
        $hours = range(10, 22); // 10:00 - 22:00
        
        $heatmap = [];
        
        foreach ($days as $day) {
            foreach ($hours as $hour) {
                $record = $data->firstWhere(function($item) use ($day, $hour) {
                    return $item->day == $day && $item->hour == $hour;
                });
                
                $count = $record ? $record->transaction_count : 0;
                
                $heatmap[$day][$hour] = [
                    'count' => $count,
                    'avg_value' => $record ? round($record->avg_value) : 0,
                ];
            }
        }
        
        return $heatmap;
    }
    
    protected function getMaxTransactions(): int
    {
        $max = 0;
        foreach ($this->heatmapData as $day => $hours) {
            foreach ($hours as $hour => $data) {
                if ($data['count'] > $max) {
                    $max = $data['count'];
                }
            }
        }
        return $max ?: 1;
    }
    
    protected function getIntensity($count): float
    {
        return $this->maxTransactions > 0 
            ? min(100, ($count / $this->maxTransactions) * 100) 
            : 0;
    }
    
    protected function getColorClass($intensity): string
    {
        if ($intensity < 25) return 'bg-green-100';
        if ($intensity < 50) return 'bg-green-300';
        if ($intensity < 75) return 'bg-yellow-300';
        return 'bg-red-400';
    }
    
    protected function getColorStyle($intensity): string
    {
        // Gradient biru untuk background
        $baseColor = [79, 70, 229]; // #4F46E5
        $opacity = $intensity / 100;
        return "background-color: rgba({$baseColor[0]}, {$baseColor[1]}, {$baseColor[2]}, {$opacity});";
    }
    
    public function getDayName(string $englishDay): string
    {
        $days = [
            'Monday' => 'Sen',
            'Tuesday' => 'Sel',
            'Wednesday' => 'Rab',
            'Thursday' => 'Kam',
            'Friday' => 'Jum',
            'Saturday' => 'Sab',
            'Sunday' => 'Min',
        ];
        
        return $days[$englishDay] ?? substr($englishDay, 0, 3);
    }
    
    public function getHoursRange(): array
    {
        // Return range jam yang ingin ditampilkan
        return range(10, 22);
    }
}
