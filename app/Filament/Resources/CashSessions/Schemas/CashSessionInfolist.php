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
                // 🧾 Header Informasi Sesi
                Section::make('Informasi Sesi Kasir')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Kasir')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('primary'),

                                TextEntry::make('opened_at')
                                    ->label('Dibuka')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-clock')
                                    ->iconColor('success'),

                                TextEntry::make('closed_at')
                                    ->label('Ditutup')
                                    ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i') : 'Masih Aktif')
                                    ->icon('heroicon-o-clock')
                                    ->iconColor(fn ($state) => $state ? 'danger' : 'warning'),

                                TextEntry::make('transaction_count')
                                    ->label('Total Transaksi')
                                    ->icon('heroicon-o-document-text')
                                    ->iconColor('info'),
                            ]),
                    ])->columnSpanFull(),

                // 💰 Ringkasan Keuangan
                Section::make('Ringkasan Keuangan')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                // 🧮 Kas
                                Section::make('Kas')
                                    ->schema([
                                        TextEntry::make('cash_in_hand')
                                            ->label('Kas Awal')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state)),

                                        TextEntry::make('total_cash_sales')
                                            ->label('Penjualan Cash')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state)),

                                        TextEntry::make('expected_cash')
                                            ->label('Total Uang di Laci (Seharusnya)')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state))
                                            ->color('success'),

                                        TextEntry::make('cash_out')
                                            ->label('Kas Tutup (Aktual)')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state))
                                            ->hidden(fn ($record) => $record->cash_out === null),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->formatStateUsing(fn ($record) => $record->cash_out ? 'Selesai' : 'Sesi Masih Berjalan')
                                            ->color(fn ($record) => $record->cash_out ? 'danger' : 'warning'),

                                        TextEntry::make('cash_difference')
                                            ->label('Selisih')
                                            ->formatStateUsing(function ($state) {
                                                if (is_null($state)) return '-';
                                                $formatted = self::formatCurrency(abs($state));
                                                return $formatted . ' (' . ($state >= 0 ? 'LEBIH' : 'KURANG') . ')';
                                            })
                                            ->color(fn ($state) => is_null($state) ? 'gray' : ($state >= 0 ? 'success' : 'danger'))
                                            ->weight('bold'),
                                    ]),

                                // 📊 Breakdown Penjualan
                                Section::make('Breakdown Penjualan')
                                    ->schema([
                                        TextEntry::make('total_cash_sales')
                                            ->label('Cash')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state)),

                                        TextEntry::make('total_non_cash_sales')
                                            ->label('Non-Cash')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state)),

                                        TextEntry::make('total_completed_sales')
                                            ->label('TOTAL PENJUALAN')
                                            ->formatStateUsing(fn ($state) => self::formatCurrency($state))
                                            ->color('success'),
                                    ]),

                                // 📈 Statistik
                                Section::make('Statistik')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('transaction_count')
                                                    ->label('Total Transaksi')
                                                    ->icon('heroicon-o-document-text')
                                                    ->color('primary'),

                                                TextEntry::make('average_transaction')
                                                    ->label('Rata-rata per Transaksi')
                                                    ->formatStateUsing(fn ($state) => self::formatCurrency($state))
                                                    ->icon('heroicon-o-currency-dollar')
                                                    ->color('success'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected static function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }
}
