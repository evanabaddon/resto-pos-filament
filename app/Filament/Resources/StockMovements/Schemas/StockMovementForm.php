<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name', fn($query) => $query->whereIn('type', ['raw', 'retail']))
                    ->label(__('messages.product_resource'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = \App\Models\Product::with('unit')->find($state);
                            $set('_product_unit', $product?->unit?->symbol ?? '');
                        }
                    })
                    ->required(),

                Select::make('type')
                    ->label(__('messages.type'))
                    ->options([
                        'increase' => __('messages.stock_in') . ' (+)',
                        'decrease' => __('messages.stock_out') . ' (-)',
                    ])
                    ->required(),

                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(1)
                    ->label(__('messages.quantity'))
                    ->suffix(fn($get) => $get('_product_unit') ?: '')
                    ->required(),

                Select::make('reason')
                    ->label(__('messages.reason'))
                    ->options([
                        'purchase' => __('messages.new_purchase'),
                        'damage' => __('messages.damaged_expired'),
                        'gift' => __('messages.gift_bonus'),
                        'other' => __('messages.other'),
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label(__('messages.notes'))
                    ->rows(2),
            ]);
    }
}
