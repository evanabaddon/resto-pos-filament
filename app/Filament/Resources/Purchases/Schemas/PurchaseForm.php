<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Purchase;
use Filament\Schemas\Schema;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Repeater\TableColumn;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        // Ambil parameter dari URL
        $productId = request()->query('product_id');
        $quantity = request()->query('quantity', 1);

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
                    ->label(__('messages.supplier_name')),

                Select::make('status')
                    ->options([
                        'draft' => __('messages.status_draft'),
                        'received' => __('messages.status_received'),
                    ])
                    ->default('draft')
                    ->required(),

                Select::make('fund_source')
                    ->label(__('messages.fund_source'))
                    ->options(Purchase::getFundSources())
                    ->default(Purchase::FUND_SOURCE_CASHIER)
                    ->required()
                    ->native(false),

                Repeater::make('items')
                    ->relationship()
                    ->table([
                        TableColumn::make(__('messages.product_name')),
                        TableColumn::make(__('messages.unit')),
                        TableColumn::make(__('messages.quantity')),
                        TableColumn::make(__('messages.price')),
                        TableColumn::make(__('messages.subtotal')),
                    ])
                    ->schema([
                        Select::make('product_id')
                            ->relationship(
                                name: 'product',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->whereIn('type', ['raw', 'retail'])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->default(function () use ($productId) {
                                if ($productId) {
                                    return $productId;
                                }
                                return null;
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Reset unit dan price ketika produk berubah
                                $set('unit_id', null);
                                $set('price', null);

                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        // Set unit_id otomatis dari produk
                                        if ($product->unit_id) {
                                            $set('unit_id', $product->unit_id);
                                        }
                                        // Set price otomatis dari base_price produk
                                        if ($product->base_price) {
                                            $set('price', $product->base_price);
                                        }
                                    }
                                }

                                // Hitung ulang subtotal
                                $set('subtotal', ($get('price') ?? 0) * ($get('quantity') ?? 0));
                            }),

                        Select::make('unit_id')
                            ->label(__('messages.unit'))
                            ->options(function (callable $get, $state) {
                                // Dapatkan product_id dari state repeater
                                $productId = $get('product_id');

                                if (!$productId) {
                                    // Jika belum ada produk yang dipilih, tampilkan semua unit
                                    return \App\Models\Unit::all()->pluck('name', 'id');
                                }

                                // Ambil produk dan unit yang terkait
                                $product = \App\Models\Product::find($productId);
                                if ($product && $product->unit_id) {
                                    // Tampilkan hanya unit yang terkait dengan produk
                                    return \App\Models\Unit::where('id', $product->unit_id)
                                        ->pluck('name', 'id');
                                }

                                return \App\Models\Unit::all()->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

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
