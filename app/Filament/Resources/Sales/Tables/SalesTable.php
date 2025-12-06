<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\Sale;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($state) => $state->diffForHumans()),
                TextColumn::make('invoice_number')->label('Invoice Number')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer Name')->searchable()->sortable(),
                TextColumn::make('order_type')->label('Order Type')->searchable()->sortable(),
                TextColumn::make('final_total')
                    ->label('Total Amount')
                    ->sortable()
                    ->money('IDR')->summarize(Sum::make()->money('IDR')->label('Total Penjualan')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DateRangeFilter::make('created_at')->label('Tanggal Transaksi'),
            ])
            ->recordActions([
                EditAction::make(),
                
                // Action untuk preview struk dengan modal
                Action::make('previewReceipt')
                    ->label('Preview Struk')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Preview Struk')
                    ->modalContent(function (Sale $record) {
                        // Load relationships
                        $sale = $record->load(['items.product', 'paymentMethod', 'user']);
                        
                        return view('filament.components.receipt-preview-content', [
                            'sale' => $sale
                        ]);
                    })
                    ->modalFooterActions([
                        Action::make('print')
                            ->label('Cetak Struk')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->action(function (Sale $record) {
                                return self::printReceiptDirect($record);
                            }),
                        Action::make('close')
                            ->label('Tutup')
                            ->color('gray')
                            ->close(), // Gunakan close() bukan cancel()
                    ])
                    ->modalWidth('sm'),

                // Action untuk print langsung
                Action::make('printReceipt')
                    ->label('Print Struk')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (Sale $record) {
                        return self::printReceiptDirect($record);
                    })
                    ->requiresConfirmation() 
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Method untuk print langsung
    public static function printReceiptDirect(Sale $sale)
    {
        $receiptContent = self::generateReceiptContent($sale);
        
        // Return JavaScript untuk print
        return <<<HTML
        <script>
            function printReceipt() {
                const content = `{$receiptContent}`;
                
                const printWindow = window.open('', '_blank', 'width=350,height=600');
                const printStyle = `
                    <style>
                        @media print {
                            body { 
                                margin: 0; 
                                padding: 10px; 
                                font-family: 'Courier New', monospace;
                                font-size: 12px;
                                width: 80mm;
                            }
                            .text-center { text-align: center; }
                            .font-bold { font-weight: bold; }
                            .text-lg { font-size: 14px; }
                            .text-sm { font-size: 11px; }
                            .text-xs { font-size: 10px; }
                            .uppercase { text-transform: uppercase; }
                            .flex { display: flex; }
                            .justify-between { justify-content: space-between; }
                            .items-start { align-items: flex-start; }
                            .flex-1 { flex: 1; }
                            .border-t { border-top: 1px solid #000; }
                            .border-dashed { border-style: dashed; }
                            .my-2 { margin-top: 0.5rem; margin-bottom: 0.5rem; }
                            .font-semibold { font-weight: 600; }
                            .space-y-1 > * + * { margin-top: 0.25rem; }
                            .space-y-2 > * + * { margin-top: 0.5rem; }
                            .pt-1 { padding-top: 0.25rem; }
                        }
                    </style>
                `;

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Struk #{$sale->invoice_number}</title>
                        \${printStyle}
                    </head>
                    <body onload="window.print(); setTimeout(() => window.close(), 500);">
                        \${content}
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
            }
            
            printReceipt();
        </script>
        HTML;
    }

    protected static function generateReceiptContent(Sale $sale): string
    {
        $content = "";
        
        // Header
        $content .= "<div class='text-center'>";
        $content .= "<h1 class='font-bold text-lg uppercase'>STRUK PEMBAYARAN</h1>";
        $content .= "<p class='text-sm'>" . config('app.name') . "</p>";
        $content .= "<p class='text-xs'>" . $sale->created_at->format('d/m/Y H:i') . "</p>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Info Transaksi
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>No. Transaksi:</span><span class='font-semibold'>" . $sale->invoice_number . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Kasir:</span><span>" . ($sale->user->name ?? 'System') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Customer:</span><span>" . ($sale->customer_name ?? 'Umum') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Status:</span><span class='font-semibold'>" . strtoupper($sale->status) . "</span></div>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Items
        $content .= "<div class='space-y-2'>";
        foreach ($sale->items as $item) {
            $content .= "<div class='flex justify-between items-start'>";
            $content .= "<div class='flex-1'>";
            $content .= "<div class='font-semibold'>" . ($item->product->name ?? 'Produk') . "</div>";
            $content .= "<div class='text-xs text-gray-600'>" . $item->quantity . " × Rp" . number_format($item->unit_price, 0, ',', '.') . "</div>";
            $content .= "</div>";
            $content .= "<div class='font-semibold'>Rp" . number_format($item->subtotal, 0, ',', '.') . "</div>";
            $content .= "</div>";
        }
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Summary
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Subtotal:</span><span>Rp" . number_format($sale->subtotal, 0, ',', '.') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Pajak (10%):</span><span>Rp" . number_format($sale->tax, 0, ',', '.') . "</span></div>";
        if ($sale->discount > 0) {
            $content .= "<div class='flex justify-between text-green-600'><span>Diskon:</span><span>- Rp" . number_format($sale->discount, 0, ',', '.') . "</span></div>";
        }
        $content .= "<div class='border-t border-gray-300 pt-1'>";
        $content .= "<div class='flex justify-between font-bold'><span>TOTAL:</span><span>Rp" . number_format($sale->final_total, 0, ',', '.') . "</span></div>";
        $content .= "</div>";
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Payment Info
        $content .= "<div class='space-y-1 text-sm'>";
        $content .= "<div class='flex justify-between'><span>Metode Bayar:</span><span class='font-semibold'>" . ($sale->paymentMethod->name ?? 'Cash') . "</span></div>";
        $content .= "<div class='flex justify-between'><span>Dibayar:</span><span>Rp" . number_format($sale->amount_paid, 0, ',', '.') . "</span></div>";
        
        if (($sale->paymentMethod->code ?? 'cash') === 'cash') {
            $change = $sale->amount_paid - $sale->final_total;
            if ($change > 0) {
                $content .= "<div class='flex justify-between'><span>Kembali:</span><span class='font-semibold'>Rp" . number_format($change, 0, ',', '.') . "</span></div>";
            }
        }
        
        $paymentStatus = $sale->is_paid ? 'LUNAS' : 'BELUM LUNAS';
        $statusColor = $sale->is_paid ? 'text-green-600' : 'text-red-600';
        $content .= "<div class='flex justify-between {$statusColor}'><span>Status Bayar:</span><span class='font-semibold'>{$paymentStatus}</span></div>";
        
        $content .= "</div>";
        
        $content .= "<div class='border-t border-dashed border-gray-300 my-2'></div>";
        
        // Footer
        $content .= "<div class='text-center text-xs'>";
        $content .= "<p>Terima kasih atas kunjungan Anda</p>";
        if (!$sale->is_paid) {
            $content .= "<p class='text-red-600 font-semibold'>*** MENUNGGU PEMBAYARAN ***</p>";
        } else {
            $content .= "<p class='font-semibold'>*** SELAMAT MENIKMATI ***</p>";
        }
        $content .= "</div>";
        
        return $content;
    }
}