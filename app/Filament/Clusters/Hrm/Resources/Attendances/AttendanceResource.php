<?php

namespace App\Filament\Clusters\Hrm\Resources\Attendances;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\Attendances\Pages;
use App\Filament\Clusters\Hrm\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Clusters\Hrm\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    protected static ?string $cluster = HrmCluster::class;

    protected static ?string $navigationLabel = 'Absensi';

    // RBAC: super_admin, admin, accountant
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'accountant']);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
