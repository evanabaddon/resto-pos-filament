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
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use App\Filament\Pages\WhatsappCenter;
use Filament\Tables\Actions\Action as TableAction;

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
                    ->searchable()
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconColor('success')
                    ->action(
                        TableAction::make('chat_wa')
                            ->url(function ($record) {
                                if (!$record->customer_phone) return null;
                                $phone = preg_replace('/[^0-9]/', '', $record->customer_phone);
                                if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
                                elseif (substr($phone, 0, 1) === '8') $phone = '62' . $phone;
                                $jid = $phone . '@s.whatsapp.net';
                                return WhatsappCenter::getUrl(['jid' => $jid]);
                            })
                    ),

                TextColumn::make('party_size')
                    ->label('Jumlah')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state > 10 ? 'warning' : 'success'),

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
                            ->when($data['from'], fn($q) => $q->whereDate('reservation_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('reservation_date', '<=', $data['until']));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('payDeposit')
                    ->label('Bayar DP')
                    ->icon('heroicon-o-credit-card')
                    ->color('info')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->schema(fn(Reservation $record) => [
                        TextEntry::make('total_estimation')
                            ->label('Total Estimasi Pesanan')
                            ->money('IDR'),

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
                            ['name' => 'Down Payment (DP)'],
                            [
                                'type' => 'service',
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
                            'order_type' => 'Dine In',
                            'customer_name' => $record->customer_name,
                            'payment_method_id' => $data['payment_method_id'],
                            'user_id' => auth()->id(),
                            'subtotal' => $data['amount'],
                            'tax' => 0,
                            'discount' => 0,
                            'final_total' => $data['amount'],
                            'total' => $data['amount'],
                            'cash_session_id' => $activeSession->id,
                            'status' => 'completed',
                            'is_paid' => true,
                            'paid_at' => now(),
                            'invoice_number' => 'DP-' . date('Ymd') . '-' . strtoupper(uniqid()),
                            'note' => 'DP: ' . $record->customer_name . ' (Res #' . $record->id . ') - ' . ($data['notes'] ?? ''),
                        ]);

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $depositProduct->id,
                            'product_name' => 'Down Payment (DP)', // Snapshot name
                            'quantity' => 1,
                            'unit_price' => $data['amount'],
                            'subtotal' => $data['amount'],
                            'notes' => 'DP: ' . $record->customer_name . ' (Res #' . $record->id . ')',
                        ]);

                        // UPDATE Reservation deposit_amount
                        $record->increment('deposit_amount', $data['amount']);

                        Notification::make()
                            ->title('Deposit Berhasil')
                            ->success()
                            ->send();
                    }),

                Action::make('convertToSale')
                    ->label('Proses ke Kasir')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    // Remove form() as we automate everything
                    ->schema([])
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

                        // 1. Create Sale header
                        $sale = Sale::create([
                            'reservation_id' => $record->id,
                            'order_type' => 'Dine In',
                            'customer_name' => $record->customer_name,
                            'user_id' => auth()->id(),
                            'cash_session_id' => $activeSession->id,
                            'status' => 'draft',
                            'invoice_number' => 'RSVP-' . date('Ymd') . '-' . strtoupper(uniqid()),
                            'subtotal' => 0,
                            'tax' => 0,
                            'discount' => 0,
                            'final_total' => 0,
                            'total' => 0,
                            'note' => 'Reservation #' . $record->id,
                        ]);

                        // 2. Clone Items (Snapshot Name)
                        $itemsSubtotal = 0;
                        foreach ($record->items as $item) {
                            $product = $item->product;
                            if (!$product) continue;

                            $price = $item->unit_price;
                            $subtotal = $price * $item->quantity;

                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => $item->product_id,
                                'product_name' => $product->name, // Snapshot name
                                'quantity' => $item->quantity,
                                'unit_price' => $price,
                                'subtotal' => $subtotal,
                                'notes' => $item->note,
                            ]);

                            $itemsSubtotal += $subtotal;
                        }

                        // 3. Handle Deposit Deduction (Named Item)
                        if ($record->deposit_amount > 0) {
                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => null, // Custom Item
                                'product_name' => 'Down Payment (DP)', // Explicit Name
                                'quantity' => 1,
                                'unit_price' => -$record->deposit_amount,
                                'subtotal' => -$record->deposit_amount,
                                'notes' => 'Potongan DP Reservasi',
                            ]);
                            $itemsSubtotal -= $record->deposit_amount;
                        }

                        // 4. Update Sale Total
                        $sale->update([
                            'subtotal' => $itemsSubtotal,
                            'final_total' => $itemsSubtotal,
                            'total' => $itemsSubtotal,
                        ]);

                        // 5. Update Reservation Status
                        $record->update(['status' => 'seated']);

                        Notification::make()
                            ->title('Transaksi Berhasil Dibuat')
                            ->body("Sale #{$sale->invoice_number} telah dibuat.")
                            ->success()
                            ->send();

                        // 6. Redirect
                        return redirect()->to('/admin/sales/' . $sale->id . '/edit');
                    }),

                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Reservation $record) => $record->status === 'pending')
                    ->action(fn(Reservation $record) => $record->update(['status' => 'confirmed'])),

                Action::make('seat')
                    ->label('Dudukkan')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->visible(fn(Reservation $record) => $record->status === 'confirmed')
                    ->action(fn(Reservation $record) => $record->update(['status' => 'seated'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
