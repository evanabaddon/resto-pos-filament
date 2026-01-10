<?php

namespace App\Filament\Clusters\Hrm\Resources\Shifts;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Models\Shift;
use BackedEnum;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $cluster = HrmCluster::class;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    // RBAC: super_admin, admin
    public static function canViewAny(): bool
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

    public static function getNavigationLabel(): string
    {
        return __('messages.shifts_resource');
    }

    public static function getModelLabel(): string
    {
        return __('messages.shift_resource');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.shifts_resource');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.hrm_cluster');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.shift_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('start_time')
                    ->label(__('messages.start_time'))
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label(__('messages.end_time'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.shift_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('messages.start_time'))
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('messages.end_time'))
                    ->time('H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Clusters\Hrm\Resources\Shifts\Pages\ManageShifts::route('/'),
        ];
    }
}
