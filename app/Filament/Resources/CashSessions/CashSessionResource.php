<?php

namespace App\Filament\Resources\CashSessions;

use App\Filament\Resources\CashSessions\Pages\CreateCashSession;
use App\Filament\Resources\CashSessions\Pages\EditCashSession;
use App\Filament\Resources\CashSessions\Pages\ListCashSessions;
use App\Filament\Resources\CashSessions\Pages\ViewCashSession;
use App\Filament\Resources\CashSessions\Schemas\CashSessionForm;
use App\Filament\Resources\CashSessions\Schemas\CashSessionInfolist;
use App\Filament\Resources\CashSessions\Tables\CashSessionsTable;
use App\Models\CashSession;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Table;

class CashSessionResource extends Resource
{
    protected static ?string $model = CashSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Sesi Kasir';

    // RBAC: super_admin, admin, accountant
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin', 'accountant']);
    }

    public static function canCreate(): bool
    {
        return false; // Typically created automatically via POS
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, ['super_admin', 'admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return CashSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CashExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashSessions::route('/'),
            'create' => CreateCashSession::route('/create'),
            'view' => ViewCashSession::route('/{record}'),
            'edit' => EditCashSession::route('/{record}/edit'),
        ];
    }
}
