<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Sale;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\PaymentMethod;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Penjualan')
                    ->schema([
                        Group::make([
                            TextInput::make('invoice_number')
                                ->label('Nomor Invoice')
                                ->required()
                                ->unique(Sale::class, 'invoice_number', ignoreRecord: true)
                                ->default(fn() => static::generateInvoiceNumber())
                                ->disabled(fn($operation) => $operation === 'edit'),

                            TextInput::make('customer_name')
                                ->label('Nama Pelanggan')
                                ->required()
                                ->maxLength(255),

                            Select::make('user_id')
                                ->label('Kasir')
                                ->relationship('user', 'name')
                                ->default(auth()->id())
                                ->required()
                                ->searchable()
                                ->preload(),

                            ToggleButtons::make('order_type')
                                ->label('Tipe Pesanan')
                                ->required()
                                ->options([
                                    'dine_in' => 'Dine In',
                                    'take_away' => 'Take Away',
                                    'delivery' => 'Delivery',
                                ])
                                ->icons([
                                    'dine_in' => 'heroicon-m-building-storefront',
                                    'take_away' => 'heroicon-m-truck',
                                    'delivery' => 'heroicon-m-home',
                                ])
                                ->inline()
                                ->default('dine_in'),
                        ])->columns(2),

                        // Select::make('cash_session_id')
                        //     ->label('Sesi Kasir')
                        //     ->relationship(
                        //         'session',
                        //         'id',
                        //         fn(Builder $query) => $query->where('status', 'active')
                        //     )
                        //     ->searchable()
                        //     ->preload()
                        //     ->required(),

                        Textarea::make('note')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Item Penjualan')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produk')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('product_name', $product->name);
                                                $set('unit_price', $product->price);
                                            }
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        static::calculateItemTotal($set, $get);
                                        static::recalculateTotals($set, $get);
                                    }),

                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        static::calculateItemTotal($set, $get);
                                        static::recalculateTotals($set, $get);
                                    }),

                                TextInput::make('discount')
                                    ->label('Diskon Item')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        static::calculateItemTotal($set, $get);
                                        static::recalculateTotals($set, $get);
                                    }),

                                TextInput::make('item_total')
                                    ->label('Subtotal Item')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->deleteAction(
                                fn(Action $action) => $action->requiresConfirmation(),
                            )
                            ->addAction(
                                fn(Action $action) => $action->label('Tambah Item'),
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::recalculateTotals($set, $get);
                            })
                            ->reorderableWithButtons(),
                    ]),

                Section::make('Ringkasan Pembayaran')
                    ->schema([
                        Group::make([
                            TextInput::make('subtotal_display')
                                ->label('Subtotal')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('subtotal') ?? 0, 0, ',', '.');
                                }),

                            TextInput::make('discount_total')
                                ->label('Diskon Total')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('Rp')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    static::recalculateTotals($set, $get);
                                }),

                            TextInput::make('tax_percentage')
                                ->label('Pajak (%)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    static::recalculateTotals($set, $get);
                                }),
                        ])->columns(3),

                        Group::make([
                            TextInput::make('tax_amount_display')
                                ->label('Nominal Pajak')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('tax_amount') ?? 0, 0, ',', '.');
                                }),

                            TextInput::make('final_total_display')
                                ->label('Total Akhir')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('final_total') ?? 0, 0, ',', '.');
                                }),

                            Hidden::make('subtotal'),
                            Hidden::make('tax_amount'),
                            Hidden::make('final_total'),
                            Hidden::make('total'),
                        ])->columns(2),
                    ]),

                Section::make('Pembayaran')
                    ->schema([
                        Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->relationship('paymentMethod', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $paymentMethod = PaymentMethod::find($state);
                                if ($paymentMethod) {
                                    $set('payment_method', $paymentMethod->name);
                                }
                            }),

                        Hidden::make('payment_method'),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Dibayar',
                                'cancelled' => 'Dibatalkan',
                                'refunded' => 'Dikembalikan',
                            ])
                            ->default('pending'),

                        Group::make([
                            Select::make('split_from')
                                ->label('Split Dari Penjualan')
                                ->relationship('splitFrom', 'invoice_number')
                                ->searchable()
                                ->preload()
                                ->visible(fn($operation) => $operation === 'create'),

                            TextInput::make('split_number')
                                ->label('Nomor Split')
                                ->numeric()
                                ->minValue(1)
                                ->visible(function ($get) {
                                    return !empty($get('split_from'));
                                }),

                            TextInput::make('split_into')
                                ->label('Split Menjadi')
                                ->numeric()
                                ->minValue(2)
                                ->visible(function ($get) {
                                    return !empty($get('split_from'));
                                }),
                        ])->columns(3)->visible(fn($operation) => $operation === 'create'),
                    ]),
            ]);
    }

    protected static function calculateItemTotal($set, $get): void
    {
        $quantity = floatval($get('quantity') ?? 0);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $discount = floatval($get('discount') ?? 0);
        
        $itemTotal = ($quantity * $unitPrice) - $discount;
        $set('item_total', max(0, $itemTotal));
    }

    protected static function recalculateTotals($set, $get): void
    {
        // Hitung subtotal dari semua items
        $items = $get('../../items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $itemDiscount = floatval($item['discount'] ?? 0);
            
            $itemTotal = ($quantity * $unitPrice) - $itemDiscount;
            $subtotal += max(0, $itemTotal);
        }

        $discountTotal = floatval($get('discount_total') ?? 0);
        $taxPercentage = floatval($get('tax_percentage') ?? 0);
        
        $taxAmount = ($subtotal - $discountTotal) * ($taxPercentage / 100);
        $finalTotal = ($subtotal - $discountTotal) + $taxAmount;

        $set('subtotal', $subtotal);
        $set('tax_amount', $taxAmount);
        $set('final_total', $finalTotal);
        $set('total', $finalTotal);
    }

    protected static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = Sale::where('invoice_number', 'like', "{$prefix}{$date}%")->latest()->first();
        
        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = intval(substr($lastInvoice->invoice_number, -4));
            $sequence = $lastSequence + 1;
        }

        return "{$prefix}{$date}" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}