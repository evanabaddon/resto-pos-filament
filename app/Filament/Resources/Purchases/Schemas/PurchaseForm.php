<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater\TableColumn;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required()
                    ->default(fn() => 'INV-' . now()->format('ymdHis'))
                    ->disabled(fn($record) => filled($record)),

                DatePicker::make('date')
                    ->required()
                    ->default(now()),

                TextInput::make('supplier_name')
                    ->label('Supplier'),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'received' => 'Diterima',
                    ])
                    ->default('draft')
                    ->required(),

                Repeater::make('items')
                    ->relationship()
                    ->table([
                        TableColumn::make('Nama Produk'),
                        TableColumn::make('Unit'),
                        TableColumn::make('QTY'),
                        TableColumn::make('Harga'),
                        TableColumn::make('Subtotal'),
                    ])
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required(),

                        Select::make('unit_id')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', ($get('price') ?? 0) * ($state ?? 0));
                            }),

                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', ($get('quantity') ?? 0) * ($state ?? 0));
                            }),

                        TextInput::make('subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // hitung total setiap kali items berubah
                        $total = collect($state)
                            ->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                        $set('total', $total);
                    }),

                TextInput::make('total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->default(0)
                    ->reactive()
                    ->columnSpanFull(),
            ]);
    }
}
