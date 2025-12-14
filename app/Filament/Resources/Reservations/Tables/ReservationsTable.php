<?php

namespace App\Filament\Resources\Reservations\Tables;

use Filament\Tables\Table;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Reservations\Widgets\ReservationCalendarWidget;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

class ReservationsTable
{
    // Widgets yang akan muncul di HEADER (atas tabel)
    protected function getHeaderWidgets(): array
    {
        return [
            ReservationCalendarWidget::class,
        ];
    }
    protected function getHeaderWidgetsColumns(): int|array
    {
        return 1; // Calendar full width
    }
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer_phone')
                    ->label('Telepon')
                    ->searchable(),
                    
                TextColumn::make('party_size')
                    ->label('Jumlah')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 10 ? 'warning' : 'success'),
                    
                TextColumn::make('reservation_date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'seated',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'confirmed',
                        'heroicon-o-user-group' => 'seated',
                        'heroicon-o-check-badge' => 'completed',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'seated' => 'Seated',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Filter::make('reservation_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('reservation_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('reservation_date', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('payDeposit')
                    ->label('Bayar DP')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form(fn (Reservation $record) => [
                        Placeholder::make('total_estimation')
                            ->label('Total Estimasi Pesanan')
                            ->content('Rp ' . number_format($record->items->sum('total_price'), 0, ',', '.')),
                            
                        Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->options(PaymentMethod::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Jumlah DP')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                        Textarea::make('notes')
                            ->label('Catatan'),
                    ])
                    ->action(function (Reservation $record, array $data) {
                        // 1. Cari atau Buat Product khusus Deposit
                        // Cari unit default (misal "Pcs" atau apapun yg pertama)
                        // This logic assumes at least one unit exists. Ideally "Pcs".
                        $defaultUnit = \App\Models\Unit::where('name', 'Pcs')->first() 
                                       ?? \App\Models\Unit::first();

                        $activeSession = \App\Models\CashSession::where('user_id', auth()->id())
                            ->where('status', 'open')
                            ->first();


                        if (!$activeSession) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Anda belum membuka sesi kasir. Silakan buka sesi terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $depositProduct = Product::firstOrCreate(
                            ['name' => 'Deposit Reservasi'],
                            [
                                'type' => 'retail',
                                'unit_id' => $defaultUnit?->id, // Assign Unit ID
                                'is_sellable' => true,
                                'base_price' => 0,
                                'sell_price' => 0, // Harga 0 = Open Price / Variabel. 
                                'stock' => 0,
                            ]
                        );

                        // Fallback check if unit_id is strictly required and we found none (though unlikely in prod)
                        if (!$depositProduct->unit_id && $defaultUnit) {
                             $depositProduct->update(['unit_id' => $defaultUnit->id]);
                        }

                        $sale = Sale::create([
                            'reservation_id' => $record->id,
                            'order_type' => 'Dine In', // Default to Dine In for Reservations
                            'customer_name' => $record->customer_name,
                            'payment_method_id' => $data['payment_method_id'],
                            'user_id' => auth()->id(), // Assign current user
                            'total' => $data['amount'],
                            'final_total' => $data['amount'],
                            'cash_session_id' => $activeSession->id,
                            'status' => 'completed',
                            'invoice_number' => 'DP-' . time(),
                            'note' => 'Deposit: ' . $record->customer_name . ' (Res #' . $record->id . ') - Rp ' . number_format($data['amount'], 0, ',', '.') . '. ' . ($data['notes'] ?? ''),
                        ]);

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $depositProduct->id,
                            'quantity' => 1,
                            'unit_price' => $data['amount'],
                            'subtotal' => $data['amount'],
                            'notes' => 'Deposit: ' . $record->customer_name . ' (Res #' . $record->id . ') - Rp ' . number_format($data['amount'], 0, ',', '.') . '. ' . ($data['notes'] ?? ''),
                        ]);

                        Notification::make()
                            ->title('Deposit Berhasil')
                            ->success()
                            ->send();
                    }),

                Action::make('convertToSale')
                    ->label('Proses ke Kasir')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->requiresConfirmation()
                    // Remove form() as we automate everything
                    ->form([]) 
                    ->action(function (Reservation $record, array $data) {
                        // 0. Cek Active Session
                        $activeSession = \App\Models\CashSession::where('user_id', auth()->id())
                            ->where('status', 'open')
                            ->first();

                        if (!$activeSession) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Anda belum membuka sesi kasir. Silakan buka sesi terlebih dahulu.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 1. Hitung Total Items
                        $itemsTotal = $record->items->sum('total_price');
                        
                        // 2. Hitung Total Deposit (dari sales yg ada)
                        $totalDeposit = $record->deposits()->sum('total');

                        // 3. Create Sale
                        $sale = Sale::create([
                            'reservation_id' => $record->id,
                            'order_type' => 'Dine In',
                            'customer_name' => $record->customer_name,
                            'user_id' => auth()->id(), // Assign current user
                            'cash_session_id' => $activeSession->id, // Assign Active Session
                            'status' => 'draft', // Draft sale, not paid yet
                            'invoice_number' => 'INV-' . time(),
                            'total' => 0, // Will update
                            'note' => 'Reservation #' . $record->id,
                        ]);

                        // 4. Clone Items
                        foreach ($record->items as $item) {
                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'subtotal' => $item->total_price,
                                'notes' => $item->note,
                            ]);
                        }

                        // 5. Add Adjustment Item (Negative) IF Deposit exists
                        if ($totalDeposit > 0) {
                             // Cari Product Deposit Reservasi
                             $depositProduct = Product::where('name', 'Deposit Reservasi')->first();
                             
                             // Safety check: if deleted, recreate or find first
                             if (!$depositProduct) {
                                 // Should exist if DP was made, but handle edge case
                                 $depositProduct = Product::firstOrCreate(['name' => 'Deposit Reservasi']);
                             }

                             SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => $depositProduct->id,
                                'quantity' => 1,
                                'unit_price' => -$totalDeposit,
                                'subtotal' => -$totalDeposit,
                                'notes' => 'Potongan DP Reservation #' . $record->id,
                            ]);
                        }

                        // 6. Recalculate Sale Total
                        $finalTotal = $itemsTotal - $totalDeposit;
                        $sale->update([
                            'subtotal' => $finalTotal, // Since subtotal = sum of items (including negative adjustment)
                            'final_total' => $finalTotal,
                            'total' => $finalTotal
                        ]);

                        // 7. Redirect
                        return redirect()->to('/admin/sales/' . $sale->id . '/edit');
                    }),

                Action::make('confirm')
                     ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Reservation $record) => $record->status === 'pending')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'confirmed'])),
                    
                Action::make('seat')
                    ->label('Dudukkan')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->visible(fn (Reservation $record) => $record->status === 'confirmed')
                    ->action(fn (Reservation $record) => $record->update(['status' => 'seated'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
