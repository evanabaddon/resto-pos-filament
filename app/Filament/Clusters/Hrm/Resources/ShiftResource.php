<?php

namespace App\Filament\Clusters\Hrm\Resources;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Models\Shift;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $cluster = HrmCluster::class;

    protected static ?string $navigationLabel = 'Jadwal Shift';

    protected static ?string $modelLabel = 'Shift Kerja';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Shift')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Jam Masuk')
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Jam Pulang')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Shift')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Masuk')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Pulang')
                    ->time('H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Clusters\Hrm\Resources\Shifts\Pages\ManageShifts::route('/'),
        ];
    }
}
