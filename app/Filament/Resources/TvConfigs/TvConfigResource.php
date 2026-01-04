<?php

namespace App\Filament\Resources\TvConfigs;

use App\Filament\Resources\TvConfigs\Pages\CreateTvConfig;
use App\Filament\Resources\TvConfigs\Pages\EditTvConfig;
use App\Filament\Resources\TvConfigs\Pages\ListTvConfigs;
use App\Filament\Resources\TvConfigs\Schemas\TvConfigForm;
use App\Filament\Resources\TvConfigs\Tables\TvConfigsTable;
use App\Models\TvConfig;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TvConfigResource extends Resource
{
    protected static ?string $model = TvConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static ?string $navigationLabel = 'TV Display Config';

    protected static ?string $modelLabel = 'TV Configuration';

    protected static ?string $pluralModelLabel = 'TV Configurations';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return TvConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TvConfigsTable::configure($table);
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
            'index' => ListTvConfigs::route('/'),
            'create' => CreateTvConfig::route('/create'),
            'edit' => EditTvConfig::route('/{record}/edit'),
        ];
    }
}
