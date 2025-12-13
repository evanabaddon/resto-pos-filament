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
                                    ->label('Kasir')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                TextEntry::make('status')
                                    ->label('Status Sesi')
                                    ->badge()
                                    ->color(fn($record) => $record->cash_out ? 'danger' : 'success')
                                    ->formatStateUsing(fn($record) => $record->cash_out ? 'Selesai (Closed)' : 'Aktif (Open)'),

                                TextEntry::make('opened_at')
                                    ->label('Waktu Buka')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('closed_at')
                                    ->label('Waktu Tutup')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('Masih Berlangsung')
                                    ->icon('heroicon-o-check-circle'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Arus Kas (Cash Flow)')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('cash_in_hand')
                                    ->label('Modal Awal (Cash In Hand)')
                                    ->money('IDR'),

                                TextEntry::make('total_cash_sales')
                                    ->label('(+) Penjualan Tunai')
                                    ->money('IDR')
                                    ->color('success'),

                                TextEntry::make('total_cash_expenses')
                                    ->label('(-) Pengeluaran Tunai')
                                    ->money('IDR')
                                    ->color('danger'),

                                TextEntry::make('expected_cash')
                                    ->label('(=) Total Uang Seharusnya')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->columnSpanFull()
                                    ->separator(),

                                TextEntry::make('cash_out')
                                    ->label('Total Uang Fisik (Aktual)')
                                    ->money('IDR')
                                    ->hidden(fn($record) => $record->cash_out === null),

                                TextEntry::make('cash_difference')
                                    ->label('Selisih (Diff)')
                                    ->money('IDR')
                                    ->badge()
                                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                                    ->hidden(fn($record) => $record->cash_out === null),
                            ]),
                    ]),
                Grid::make(1)->schema([
                    Section::make('Rincian Pembayaran')
                        ->icon('heroicon-o-chart-pie')
                        ->schema([
                            \Filament\Infolists\Components\ViewEntry::make('breakdown')
                                ->view('filament.infolists.cash-session-breakdown')
                                ->hiddenLabel(),
                            TextEntry::make('transaction_count')
                                ->label('Total Transaksi')
                                ->inlineLabel(),

                            TextEntry::make('average_transaction')
                                ->label('Rata-rata Order')
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
