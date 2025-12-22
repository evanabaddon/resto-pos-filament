<?php

namespace App\Filament\Clusters\Hrm\Resources\LeaveRequests;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Clusters\Hrm\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Clusters\Hrm\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Clusters\Hrm\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Clusters\Hrm\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?string $cluster = HrmCluster::class;

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Pengajuan Izin/Cuti';

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
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
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
