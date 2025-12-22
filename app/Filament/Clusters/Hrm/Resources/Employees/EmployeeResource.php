<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\Employees\Pages;
use App\Filament\Clusters\Hrm\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Clusters\Hrm\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $cluster = HrmCluster::class;

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

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
