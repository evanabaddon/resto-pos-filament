<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\Sale;
use App\Services\ReceiptPrintService;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;
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
                    ->formatStateUsing(fn($state) => $state->diffForHumans()),
                TextColumn::make('invoice_number')->label('Invoice Number')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer Name')->searchable()->sortable(),
                TextColumn::make('order_type')->label('Order Type')->searchable()->sortable(),
                ToggleColumn::make('is_tax_reported')
                    ->label('Fiskal')
                    ->sortable()
                    ->toggleable(),
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
                                try {
                                    (new ReceiptPrintService($record))->printReceipt();
                                    Notification::make()
                                        ->title('Print job sent to webhook')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Webhook Print Failed')
                                        ->body($e->getMessage())
                                        ->warning()
                                        ->send();
                                }

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
                        try {
                            (new ReceiptPrintService($record))->printReceipt();
                            Notification::make()
                                ->title('Print job sent to webhook')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Webhook Print Failed')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }

                        return self::printReceiptDirect($record);
                    })
                    ->requiresConfirmation(),

                // Action untuk cetak ulang order (Kitchen/Bar)
                Action::make('reprintOrder')
                    ->label('Cetak Ulang Order')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Ulang Order ke Dapur/Bar')
                    ->modalDescription('Apakah Anda yakin ingin mencetak ulang pesanan ini? Print job akan dikirim kembali ke printer Dapur dan Bar.')
                    ->action(function (Sale $record) {
                        try {
                            $service = new \App\Services\OrderPrintService();
                            $service->printOrderByProductType($record);

                            Notification::make()
                                ->title('Print job ulang berhasil dikirim')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mencetak ulang')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
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
        // Get settings
        $settings = app(\App\Settings\GeneralSettings::class);
        $printerWidth = $settings->printer_width ?? '58mm';

        // Render content using Blade View
        $receiptContent = view('filament.components.receipt-preview-content', [
            'sale' => $sale,
            'settings' => $settings
        ])->render();

        // Check if environment is local/dev to enable auto-close for better UX
        $autoClose = true;
        $autoCloseScript = $autoClose ? 'setTimeout(() => window.close(), 1000);' : '';

        // Return JavaScript untuk print
        return <<<HTML
        <script>
            function printReceipt() {
                const content = `{$receiptContent}`;
                
                const printWindow = window.open('', '_blank', 'width=400,height=600');
                
                if (!printWindow) {
                    alert('Pop-up blocked! Please allow pop-ups for this site.');
                    return;
                }

                // Inject styles for printing based on settings
                const width = '{$printerWidth}';
                const printStyle = `
                    <style>
                        @media print {
                            body { 
                                margin: 0; 
                                padding: 0; 
                                font-family: 'Courier New', monospace;
                                width: \${width}; 
                            }
                            @page {
                                margin: 0;
                                size: \${width} auto; 
                            }
                        }
                        /* Screen styles if viewed in browser */
                        body {
                            font-family: 'Courier New', monospace;
                            padding: 20px;
                            background: #f3f4f6;
                            display: flex;
                            justify-content: center;
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
                    <body onload="window.print(); {$autoCloseScript}">
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
}