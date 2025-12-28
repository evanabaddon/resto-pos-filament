<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionResource\Pages;
use App\Models\Production;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\UserRole;
use BackedEnum;
use UnitEnum;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cake';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    protected static ?string $navigationLabel = 'Produksi';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        // Adjust RBAC as needed
        return in_array(auth()->user()->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Inventory, UserRole::Kitchen]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Input Produksi')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk (Kitchen/Bar)')
                            ->relationship('product', 'name', function ($query) {
                                return $query->whereIn('type', ['produced', 'bar'])
                                    ->where('enable_stock_alert', true);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Optional: Calculate estimated ingredients or show current prepared stock
                            }),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Produksi')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Oleh'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
        ];
    }
}
