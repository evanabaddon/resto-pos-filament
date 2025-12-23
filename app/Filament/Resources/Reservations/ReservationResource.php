<?php

namespace App\Filament\Resources\Reservations;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use App\Models\Reservation;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\Reservations\Pages\EditReservation;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\Pages\CreateReservation;
use App\Filament\Resources\Reservations\Schemas\ReservationForm;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Filament\Resources\Reservations\Widgets\ReservationCalendarWidget;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Reservasi Meja';

    // RBAC: super_admin, admin, cashier, waiter
    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Cashier, \App\Enums\UserRole::Waiter]);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Cashier, \App\Enums\UserRole::Waiter]);
    }

    public static function canEdit(Model $record): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin, \App\Enums\UserRole::Cashier]); // Waiter create only? Let's allow edit for now or strict? Let's allow edit.
    }

    public static function canDelete(Model $record): bool
    {
        return in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin]); // Only admins can delete
    }

    protected static ?int $navigationSort = 3;

    public static function getWidgets(): array
    {
        return [
            ReservationCalendarWidget::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return ReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
            'create' => CreateReservation::route('/create'),
            'edit' => EditReservation::route('/{record}/edit'),
        ];
    }
}
