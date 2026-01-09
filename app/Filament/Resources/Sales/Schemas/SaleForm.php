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
                Section::make(__('messages.sale_info'))
                    ->schema([
                        Group::make([
                            TextInput::make('invoice_number')
                                ->label(__('messages.invoice_number'))
                                ->required()
                                ->unique(Sale::class, 'invoice_number', ignoreRecord: true)
                                ->default(fn() => static::generateInvoiceNumber())
                                ->disabled(fn($operation) => $operation === 'edit'),

                            TextInput::make('customer_name')
                                ->label(__('messages.customer_name'))
                                ->required()
                                ->maxLength(255),

                            Select::make('user_id')
                                ->label(__('messages.cashier'))
                                ->relationship('user', 'name')
                                ->default(auth()->id())
                                ->required()
                                ->searchable()
                                ->preload(),

                            ToggleButtons::make('order_type')
                                ->label(__('messages.order_type'))
                                ->required()
                                ->options([
                                    'Dine In' => __('messages.dine_in'),
                                    'Take Away' => __('messages.take_away'),
                                    'Delivery' => __('messages.delivery'),
                                ])
                                ->icons([
                                    'Dine In' => 'heroicon-m-building-storefront',
                                    'Take Away' => 'heroicon-m-truck',
                                    'Delivery' => 'heroicon-m-home',
                                ])
                                ->inline()
                                ->default('Dine In'),
                        ])->columns(2),

                        Select::make('cash_session_id')
                            ->label(__('messages.cash_session'))
                            ->relationship('session', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Textarea::make('note')
                            ->label(__('messages.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.sale_items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('messages.product_resource'))
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

                                Hidden::make('product_name')
                                    ->dehydrated(),

                                TextInput::make('quantity')
                                    ->label(__('messages.quantity'))
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
                                    ->label(__('messages.unit_price'))
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        static::calculateItemTotal($set, $get);
                                        static::recalculateTotals($set, $get);
                                    }),

                                TextInput::make('discount')
                                    ->label(__('messages.item_discount'))
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
                                    ->label(__('messages.item_subtotal'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->formatStateUsing(function ($state, $get) {
                                        $quantity = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $discount = floatval($get('discount') ?? 0);

                                        return ($quantity * $unitPrice) - $discount;
                                    }),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->deleteAction(
                                fn(Action $action) => $action->requiresConfirmation(),
                            )
                            ->addAction(
                                fn(Action $action) => $action->label(__('messages.add_item')),
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::recalculateTotals($set, $get);
                            })
                            ->reorderableWithButtons(),
                    ]),

                Section::make(__('messages.payment_summary'))
                    ->schema([
                        Group::make([
                            TextInput::make('subtotal_display')
                                ->label(__('messages.subtotal'))
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('subtotal') ?? 0, 0, ',', '.');
                                }),

                            TextInput::make('discount')
                                ->label(__('messages.total_discount'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('Rp')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    static::recalculateTotals($set, $get);
                                }),

                            TextInput::make('tax_percentage')
                                ->label(__('messages.tax_percent'))
                                ->numeric()
                                ->default(fn() => app(\App\Settings\GeneralSettings::class)->enable_tax ? app(\App\Settings\GeneralSettings::class)->tax_percentage : 0)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    static::recalculateTotals($set, $get);
                                })
                                ->afterStateHydrated(function ($component, $state, $get) {
                                    if ($state === null && $get('tax') > 0) {
                                        $subtotal = floatval($get('subtotal') ?? 0);
                                        $discount = floatval($get('discount') ?? 0);
                                        $tax = floatval($get('tax') ?? 0);
                                        $taxableAmount = $subtotal - $discount;

                                        if ($taxableAmount > 0) {
                                            $percentage = ($tax / $taxableAmount) * 100;
                                            $component->state(round($percentage, 2));
                                        }
                                    }
                                }),
                        ])->columns(3),

                        Group::make([
                            TextInput::make('tax_amount_display')
                                ->label(__('messages.tax_amount'))
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('tax') ?? 0, 0, ',', '.');
                                }),

                            TextInput::make('final_total_display')
                                ->label(__('messages.final_total'))
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('Rp')
                                ->default(0)
                                ->formatStateUsing(function ($state, $get) {
                                    return number_format($get('final_total') ?? 0, 0, ',', '.');
                                }),

                            Hidden::make('subtotal'),
                            Hidden::make('tax'),
                            Hidden::make('final_total'),
                            Hidden::make('total'),
                        ])->columns(2),
                    ]),

                Section::make(__('messages.payment_title'))
                    ->schema([
                        Select::make('payment_method_id')
                            ->label(__('messages.payment_method'))
                            ->relationship('paymentMethod', 'name')
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
                            ->label(__('messages.status'))
                            ->required()
                            ->options([
                                'draft' => __('messages.status_pending'),
                                'completed' => __('messages.status_completed'),
                                'cancelled' => __('messages.status_cancelled'),
                                'refunded' => __('messages.status_refunded'),
                            ])
                            ->default('pending'),

                        Group::make([
                            Select::make('split_from')
                                ->label(__('messages.split_from'))
                                ->relationship('splitFrom', 'invoice_number')
                                ->searchable()
                                ->preload()
                                ->visible(fn($operation) => $operation === 'create'),

                            TextInput::make('split_number')
                                ->label(__('messages.split_number'))
                                ->numeric()
                                ->minValue(1)
                                ->visible(function ($get) {
                                    return !empty($get('split_from'));
                                }),

                            TextInput::make('split_into')
                                ->label(__('messages.split_into'))
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
        $set('item_total', $itemTotal);
    }

    protected static function recalculateTotals($set, $get): void
    {
        // Detect context: Are we inside the repeater or at root?
        // Try to get items from parent (inside repeater)
        $items = $get('../../items');
        $isInsideRepeater = !is_null($items);

        // If not found, try getting from root
        if (!$isInsideRepeater) {
            $items = $get('items') ?? [];
        }

        $subtotal = 0;

        foreach ($items as $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $itemDiscount = floatval($item['discount'] ?? 0);

            $itemTotal = ($quantity * $unitPrice) - $itemDiscount;
            $subtotal += $itemTotal;
        }

        // Determine path prefix for root fields
        $pathPrefix = $isInsideRepeater ? '../../' : '';

        // Get root-level values with prefix
        $discountTotal = floatval($get($pathPrefix . 'discount') ?? 0);
        $taxPercentage = floatval($get($pathPrefix . 'tax_percentage') ?? 0);

        $taxAmount = ($subtotal - $discountTotal) * ($taxPercentage / 100);
        $finalTotal = ($subtotal - $discountTotal) + $taxAmount;

        // Set values using prefix
        $set($pathPrefix . 'subtotal', $subtotal);
        $set($pathPrefix . 'tax', $taxAmount);
        $set($pathPrefix . 'final_total', $finalTotal);
        $set($pathPrefix . 'total', $finalTotal);

        // Update Display Fields
        $set($pathPrefix . 'subtotal_display', number_format($subtotal, 0, ',', '.'));
        $set($pathPrefix . 'tax_amount_display', number_format($taxAmount, 0, ',', '.'));
        $set($pathPrefix . 'final_total_display', number_format($finalTotal, 0, ',', '.'));
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