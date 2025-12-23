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
        return app(\App\Services\LicenseService::class)->isValid('hrm');
    }

    protected static ?string $cluster = HrmCluster::class;

    protected static ?string $navigationLabel = 'Absensi';

    // RBAC: super_admin, admin, accountant
    public static function canViewAny(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Accountant;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
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
