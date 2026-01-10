<?php

namespace App\Filament\Resources\CashSessions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class CashSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 🧾 Header & Key Metrics
                Section::make()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label(__('messages.cashier_name'))
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                TextEntry::make('status')
                                    ->label(__('messages.session_status'))
                                    ->badge()
                                    ->color(fn($record) => $record->cash_out ? 'danger' : 'success')
                                    ->formatStateUsing(fn($record) => $record->cash_out ? __('messages.closed') : __('messages.open')),

                                TextEntry::make('opened_at')
                                    ->label(__('messages.opened_at'))
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('closed_at')
                                    ->label(__('messages.closed_at'))
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder(__('messages.still_active'))
                                    ->icon('heroicon-o-check-circle'),
                            ]),
                    ])->columnSpanFull(),

                Section::make(__('messages.cash_flow'))
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('cash_in_hand')
                                    ->label(__('messages.starting_cash'))
                                    ->money('IDR'),

                                TextEntry::make('total_cash_sales')
                                    ->label(__('messages.cash_sales_add'))
                                    ->money('IDR')
                                    ->color('success'),

                                TextEntry::make('total_cash_expenses')
                                    ->label(__('messages.cash_expenses_sub'))
                                    ->money('IDR')
                                    ->color('danger'),

                                TextEntry::make('total_cash_purchases')
                                    ->label(__('messages.cash_purchases_sub'))
                                    ->money('IDR')
                                    ->color('danger'),

                                TextEntry::make('expected_cash')
                                    ->label(__('messages.expected_cash_equals'))
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->columnSpanFull()
                                    ->separator(),

                                TextEntry::make('cash_out')
                                    ->label(__('messages.actual_cash'))
                                    ->money('IDR')
                                    ->hidden(fn($record) => $record->cash_out === null),

                                TextEntry::make('cash_difference')
                                    ->label(__('messages.difference'))
                                    ->money('IDR')
                                    ->badge()
                                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                                    ->hidden(fn($record) => $record->cash_out === null),
                            ]),
                    ]),
                Grid::make(1)->schema([
                    Section::make(__('messages.payment_details'))
                        ->icon('heroicon-o-chart-pie')
                        ->schema([
                            \Filament\Infolists\Components\ViewEntry::make('breakdown')
                                ->view('filament.infolists.cash-session-breakdown')
                                ->hiddenLabel(),
                            TextEntry::make('transaction_count')
                                ->label(__('messages.total_transactions'))
                                ->inlineLabel(),

                            TextEntry::make('average_transaction')
                                ->label(__('messages.average_order'))
                                ->money('IDR')
                                ->inlineLabel(),
                        ])->columnSpan(1),
                ]),
            ]);
    }

    protected static function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }
}
