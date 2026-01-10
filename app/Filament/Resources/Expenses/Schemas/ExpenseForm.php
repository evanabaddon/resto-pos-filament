<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

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

                        Textarea::make('description')
                            ->label(__('messages.expense_description'))
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TextInput::make('amount')
                            ->label(__('messages.amount'))
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
