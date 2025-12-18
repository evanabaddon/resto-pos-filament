<?php

namespace App\Filament\Clusters\Crm\Resources;

use App\Filament\Clusters\Crm\CrmCluster;
use App\Filament\Clusters\Crm\Resources\LoyaltyTierResource\Pages;
use App\Models\LoyaltyTier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class LoyaltyTierResource extends Resource
{
    protected static ?string $model = LoyaltyTier::class;

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan (CRM)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Level';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Level')
                    ->required()
                    ->maxLength(255),
                Grid::make(2)
                    ->schema([
                        TextInput::make('min_points')
                            ->label('Min. Poin')
                            ->numeric()
                            ->default(0)
                            ->mask('999999'),
                        TextInput::make('min_visits')
                            ->label('Min. Kunjungan')
                            ->numeric()
                            ->default(0),
                    ]),
                Textarea::make('benefit_description')
                    ->label('Deskripsi Keuntungan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_visits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLoyaltyTiers::route('/'),
            'create' => Pages\CreateLoyaltyTier::route('/create'),
            'edit' => Pages\EditLoyaltyTier::route('/{record}/edit'),
        ];
    }
}