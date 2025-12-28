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
use Filament\Tables\Actions\ActionGroup;
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
                    ->dateTime()
                    ->formatStateUsing(fn($state) => $state->diffForHumans()),
                TextColumn::make('invoice_number')->label('Invoice Number')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Customer Name')->searchable()->sortable(),
                TextColumn::make('order_type')->label('Order Type')->searchable()->sortable(),
                TextColumn::make('final_total')
                    ->label('Total Amount')
                    ->sortable()
                    ->money('IDR')->summarize(Sum::make()->money('IDR')->label('Total Penjualan')),
                TextColumn::make('discount')
                    ->label('Diskon')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DateRangeFilter::make('created_at')->label('Tanggal Transaksi'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('previewReceipt')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Preview Struk')
                    ->modalContent(function (Sale $record) {
                        $sale = $record->load(['items.product', 'paymentMethod', 'user']);
                        return view('filament.components.receipt-preview-content', ['sale' => $sale]);
                    })
                    ->modalFooterActions([
                        Action::make('print')
                            ->label('Cetak')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->action(fn(Sale $record) => (new ReceiptPrintService($record))->printReceipt()),
                        Action::make('close')
                            ->label('Tutup')
                            ->color('gray')
                            ->close(),
                    ]),

                // Group Secondary Actions
                \Filament\Actions\ActionGroup::make([
                    // Print Struk Direct
                    Action::make('printReceipt')
                        ->label('Print Struk')
                        ->icon('heroicon-o-printer')
                        ->action(fn(Sale $record) => (new ReceiptPrintService($record))->printReceipt()),

                    // Print Struk HTML
                    Action::make('printHtml')
                        ->label('Print Struk (HTML)')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(fn(Sale $record) => route('sales.print', $record))
                        ->openUrlInNewTab(),

                    // Cetak Ulang Order
                    Action::make('reprintOrder')
                        ->label('Cetak Ulang Order')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            try {
                                $service = new \App\Services\OrderPrintService();
                                $service->printOrderByProductType($record);
                                Notification::make()->title('Order dikirim ulang')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    // Assign Member
                    Action::make('assignMember')
                        ->label('Klaim Poin Member')
                        ->icon('heroicon-o-user-plus')
                        ->visible(fn(Sale $record) => $record->status === 'completed' && !$record->member_id)
                        ->schema([
                            \Filament\Forms\Components\Select::make('member_id')
                                ->label('Pilih Member')
                                ->relationship('member', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Sale $record, array $data) {
                            try {
                                // 1. Hitung Poin (Dynamic based on Settings)
                                $settings = app(\App\Settings\GeneralSettings::class);
                                $exchangeRate = $settings->loyalty_point_exchange_rate ?? 10000;
                                $points = floor($record->amount_paid / $exchangeRate);
                                $member = \App\Models\Member::find($data['member_id']);
                                $record->update(['member_id' => $member->id, 'points_earned' => $points]);
                                $member->addPoints($points);
                                // Pass sale creation date to avoid "visited just now" regarding old sales
                                $member->recordVisit($record->amount_paid, $record->created_at);
                                Notification::make()->title('Poin Diklaim')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                            }
                        }),

                    // Void / Delete
                    \Filament\Actions\DeleteAction::make()
                        ->label('Void Transaksi')
                        ->modalHeading('Void Transaksi & Restore Stok')
                        ->hidden(fn() => !in_array(auth()->user()->role, [\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Admin]))
                        ->action(function (Sale $record) {
                            try {
                                $orderService = new \App\Services\OrderService();
                                $orderService->deleteSale($record->id);
                                Notification::make()->title('Transaksi Void Berhasil')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                            }
                        }),
                ])
                    ->label('Terkait')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->tooltip('Menu Aksi'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
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
