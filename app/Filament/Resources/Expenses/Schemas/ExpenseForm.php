<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.expense_info'))
                    ->schema([
                        DatePicker::make('date')
                            ->label(__('messages.date'))
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        TextInput::make('reference')
                            ->label(__('messages.reference'))
                            ->default(fn() => Expense::generateReference())
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),
                    ])
                    ->columns(2),

                Section::make(__('messages.fund_source_recipient'))
                    ->schema([
                        Select::make('fund_source')
                            ->label(__('messages.fund_source'))
                            ->required()
                            ->options(Expense::getFundSources())
                            ->default(Expense::FUND_SOURCE_CASHIER)
                            ->reactive(),

                        Select::make('payment_method_id')
                            ->label(__('messages.payment_method'))
                            ->relationship('paymentMethod', 'name', fn($query) => $query->active())
                            ->searchable()
                            ->preload()
                            ->required(fn(callable $get) => $get('fund_source') !== Expense::FUND_SOURCE_PETTY_CASH)
                            ->visible(fn(callable $get) => $get('fund_source') !== Expense::FUND_SOURCE_PETTY_CASH)
                            ->helperText(__('messages.payment_method_helper')),

                        TextInput::make('recipient')
                            ->label(__('messages.recipient'))
                            ->maxLength(255)
                            ->placeholder(__('messages.recipient_placeholder'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('messages.expense_details'))
                    ->schema([
                        Select::make('expense_category_id')
                            ->label(__('messages.expense_category'))
                            ->required()
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->maxLength(500),
                            ]),

                        Toggle::make('is_stock_purchase')
                            ->label(__('messages.is_stock') ?? 'Stok?')
                            ->helperText(__('messages.is_stock_purchase_helper') ?? 'Centang jika seluruh pengeluaran ini adalah untuk stok (HPP)')
                            ->default(false)
                            ->live(),

                        \Filament\Forms\Components\Repeater::make('items')
                            ->label(__('messages.expense_items') ?? 'Daftar Item Pengeluaran')
                            ->relationship()
                            ->schema([
                                TextInput::make('description')
                                    ->label(__('messages.description') ?? 'Deskripsi')
                                    ->required()
                                    ->columnSpan(2)
                                    ->datalist(function () {
                                        return \App\Models\ExpenseItem::query()
                                            ->select('description')
                                            ->whereNotNull('description')
                                            ->where('description', '!=', '')
                                            ->distinct()
                                            ->orderBy('description')
                                            ->limit(50)
                                            ->pluck('description')
                                            ->toArray();
                                    }),
                                TextInput::make('amount')
                                    ->label(__('messages.amount') ?? 'Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->live()
                                    ->debounce(500)
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        $items = $get('../../items') ?? [];
                                        $total = collect($items)->sum('amount');
                                        $set('../../amount', $total);
                                    }),
                                Toggle::make('is_stock_purchase')
                                    ->label(__('messages.is_stock') ?? 'Stok?')
                                    ->helperText('Centang jika item ini adalah stok (HPP)')
                                    ->default(false),
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                            ->addActionLabel(__('messages.add_item') ?? 'Tambah Item')
                            ->reorderableWithButtons()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                $items = $get('items') ?? [];
                                $total = collect($items)->sum('amount');
                                $set('amount', $total);
                            }),

                        TextInput::make('amount')
                            ->label(__('messages.total_amount') ?? 'Total Pengeluaran')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->helperText('Otomatis dihitung dari jumlah item di atas.'),
                    ])->columnSpanFull(),

                Section::make(__('messages.status_notes'))
                    ->schema([
                        Select::make('status')
                            ->label(__('messages.status'))
                            ->required()
                            ->searchable()
                            ->options([
                                'pending' => __('messages.status_pending'),
                                'approved' => __('messages.status_approved') ?? 'Approved', // Fallback or add to common/expense
                                'rejected' => __('messages.status_rejected') ?? 'Rejected',
                            ])
                            ->default('pending')
                            ->reactive(),

                        Textarea::make('notes')
                            ->label(__('messages.notes'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder(__('messages.notes_placeholder'))
                            ->columnSpanFull(),

                        FileUpload::make('receipt_path')
                            ->label('Nota/Receipt')
                            ->directory('expense-receipts')
                            ->image()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120) // 5MB
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull()
                            ->helperText('Upload nota atau bukti pembayaran (opsional). Format: JPG, PNG, atau PDF. Maksimal 5MB.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
