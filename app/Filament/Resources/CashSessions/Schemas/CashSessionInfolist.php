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
                // Header Information
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
                                
                                TextEntry::make('sales_count')
                                    ->label('Total Transaksi')
                                    ->formatStateUsing(fn ($record) => $record->sales()->where('status', 'completed')->count())
                                    ->icon('heroicon-o-document-text')
                                    ->iconColor('info'),
                            ]),
                    ])->columnSpanFull(),
                // Ringkasan Keuangan
                // Ringkasan Keuangan
                Section::make('Ringkasan Keuangan')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Section::make('Kas')
                                    ->schema([
                                        TextEntry::make('cash_in_hand')
                                            ->label('Kas Awal')
                                            ->money('IDR')
                                            ->weight('bold'),
                                        
                                        TextEntry::make('total_cash_sales')
                                            ->label('Penjualan Cash')
                                            ->money('IDR')
                                            ->weight('bold'),
                                        
                                        TextEntry::make('expected_cash')
                                            ->label('Total Uang di Laci')
                                            ->money('IDR')
                                            ->color('success')
                                            ->weight('bold')
                                            ->size('lg'),
                                        
                                        TextEntry::make('cash_out')
                                            ->label('Kas Tutup (Aktual)')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->hidden(fn ($record) => $record->cash_out === null),    

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->formatStateUsing(fn () => 'Sesi Masih Berjalan')
                                            ->color('warning')
                                            ->visible(fn ($record) => $record->cash_out === null),
                                        
                                        TextEntry::make('cash_difference')
                                            ->label('Selisih')
                                            ->formatStateUsing(function ($record) {
                                                $difference = $record->cash_out - $record->expected_cash;
                                                $formatted = 'Rp ' . number_format(abs($difference), 0, ',', '.');
                                                return $formatted . ' (' . ($difference >= 0 ? 'LEBIH' : 'KURANG') . ')';
                                            })
                                            ->color(function ($record) {
                                                $difference = $record->cash_out - $record->expected_cash;
                                                return $difference >= 0 ? 'success' : 'danger';
                                            })
                                            ->weight('bold'),
                                            // TextEntry::make('')->hidden(),
                                    ]),
                                
                                Section::make('Breakdown Penjualan')
                                    ->schema([
                                        TextEntry::make('total_cash_sales')
                                            ->label('Cash')
                                            ->money('IDR')
                                            ->weight('bold'),
                                        
                                        TextEntry::make('total_non_cash_sales')
                                            ->label('Non-Cash')
                                            ->money('IDR')
                                            ->weight('bold'),
                                
                                        TextEntry::make('total_completed_sales')
                                            ->label('TOTAL PENJUALAN')
                                            ->money('IDR')
                                            ->color('success')
                                            ->weight('bold')
                                            ->size('lg'),
                                    ]),
                                // Statistik
                                Section::make('Statistik')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('transaction_stats')
                                                    ->label('Total Transaksi')
                                                    ->formatStateUsing(fn ($record) => $record->sales()->where('status', 'completed')->count())
                                                    ->icon('heroicon-o-document-text')
                                                    ->iconColor('primary')
                                                    ->size('lg')
                                                    ->weight('bold'),
                                                
                                                TextEntry::make('average_transaction_stats')
                                                    ->label('Rata-rata per Transaksi')
                                                    ->formatStateUsing(function ($record) {
                                                        $totalSales = $record->sales()
                                                            ->where('status', 'completed')
                                                            ->sum('final_total');
                                                        $count = $record->sales()
                                                            ->where('status', 'completed')
                                                            ->count();
                                                        $average = $count > 0 ? $totalSales / $count : 0;
                                                        return 'Rp ' . number_format($average, 0, ',', '.');
                                                    })
                                                    ->icon('heroicon-o-currency-dollar')
                                                    ->iconColor('success')
                                                    ->size('lg')
                                                    ->weight('bold'),
                                            ]),
                                    ]),
                            ]),
                    ]),
                    
                
            ]);
    }

    public function getSubheading(): string|null
    {
        return $this->record->opened_at->isoFormat('dddd, D MMMM Y HH:mm');
    }

    // Helper Methods
    public function getCashSales()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('final_total');
    }

    public function getTransferSales()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'transfer')
            ->sum('final_total');
    }

    public function getQrisSales()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'qris')
            ->sum('final_total');
    }

    public function getCardSales()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->whereIn('payment_method', ['debit_card', 'credit_card'])
            ->sum('final_total');
    }

    public function getOtherSales()
    {
        $totalSales = $this->getTotalSales();
        $knownSales = $this->getCashSales() + $this->getTransferSales() + $this->getQrisSales() + $this->getCardSales();
        return max(0, $totalSales - $knownSales);
    }

    public function getTotalSales()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->sum('final_total');
    }

    public function getExpectedCash()
    {
        return $this->record->cash_in_hand + $this->getCashSales();
    }

    public function getCashDifference()
    {
        if (!$this->record->cash_out) return 0;
        return $this->record->cash_out - $this->getExpectedCash();
    }

    public function getTransactionCount()
    {
        return $this->record->sales()
            ->where('status', 'completed')
            ->count();
    }

    public function getAverageTransaction()
    {
        $count = $this->getTransactionCount();
        return $count > 0 ? $this->getTotalSales() / $count : 0;
    }

    public function getPaymentMethodLabel($method)
    {
        return match($method) {
            'cash' => 'Cash',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'debit_card' => 'Kartu Debit',
            'credit_card' => 'Kartu Kredit',
            default => ucfirst($method)
        };
    }

    public function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
