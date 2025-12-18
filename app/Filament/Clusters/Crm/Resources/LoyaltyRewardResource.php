<?php

namespace App\Filament\Clusters\Crm\Resources;

use App\Filament\Clusters\Crm\CrmCluster;
use App\Filament\Clusters\Crm\Resources\LoyaltyRewardResource\Pages;
use App\Models\LoyaltyReward;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan (CRM)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $modelLabel = 'Katalog Hadiah';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Hadiah')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Hadiah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('points_required')
                            ->label('Poin Dibutuhkan')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Select::make('product_id')
                            ->label('Produk (Optional)')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Jika diisi, penukaran akan otomatis menambahkan produk ini ke keranjang (Diskon 100%).'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_required')
                    ->label('Poin')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Menu Terkait')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
            'index' => Pages\ListLoyaltyRewards::route('/'),
            'create' => Pages\CreateLoyaltyReward::route('/create'),
            'edit' => Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }
}