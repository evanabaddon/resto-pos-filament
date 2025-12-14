<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Widgets;

use App\Models\Attendance;
use App\Models\Employee;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeAttendanceStats extends BaseWidget
{
    public ?Model $record = null;

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    protected static ?string $heading = 'Statistik Absensi Bulanan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Attendance::query()
                    ->where('employee_id', $this->record->id)
                    ->select(
                        DB::raw('MAX(id) as id'),
                        DB::raw('DATE_FORMAT(date, "%Y-%m") as month_year'),
                        DB::raw('count(*) as total_days'),
                        DB::raw('sum(case when is_late = 1 then 1 else 0 end) as total_late'),
                        DB::raw('sum(case when is_early_leave = 1 then 1 else 0 end) as total_early'),
                        DB::raw('sum(overtime_minutes) as total_overtime_minutes')
                    )
                    ->groupBy('month_year')
                    ->orderBy('month_year', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('month_year')
                    ->label('Bulan')
                    ->formatStateUsing(fn($state) => Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y')),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Hadir (Hari)'),
                Tables\Columns\TextColumn::make('total_late')
                    ->label('Terlambat (Kali)')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('total_early')
                    ->label('Pulang Cepat (Kali)')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('total_overtime_minutes')
                    ->label('Total Overtime')
                    ->formatStateUsing(fn($state) => floor($state / 60) . 'j ' . ($state % 60) . 'm'),
            ]);
    }
}
