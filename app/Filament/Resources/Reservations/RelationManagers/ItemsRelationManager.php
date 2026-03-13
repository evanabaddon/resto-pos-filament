<?php

namespace App\Filament\Resources\Reservations\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Pre-order Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name', fn(Builder $query) => $query->where('is_sellable', true))
                    ->searchable()
                    ->required()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $product = Product::find($state);
                        if ($product) {
                            $set('unit_price', $product->sell_price ?? 0);
                            $set('total_price', $product->sell_price ?? 0);
                        }
                    }),

                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->live()
                    ->debounce(500)
                    ->afterStateUpdated(fn($state, Get $get, Set $set) => $set('total_price', (float) $state * (float) $get('unit_price'))),

                TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->required()
                    ->live()
                    ->debounce(500)
                    ->afterStateUpdated(fn($state, Get $get, Set $set) => $set('total_price', (float) $state * (float) $get('quantity'))),

                TextInput::make('total_price')
                    ->label('Total')
                    ->numeric()
                    ->required()
                    ->readOnly(),

                TextInput::make('note')
                    ->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk'),
                TextColumn::make('quantity')
                    ->label('Qty'),
                TextColumn::make('unit_price')
                    ->money('IDR')
                    ->label('Harga'),
                TextColumn::make('total_price')
                    ->money('IDR')
                    ->label('Total'),
                TextColumn::make('note')
                    ->label('Catatan'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
