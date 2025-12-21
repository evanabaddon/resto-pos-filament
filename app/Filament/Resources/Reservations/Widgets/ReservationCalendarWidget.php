<?php

namespace App\Filament\Resources\Reservations\Widgets;

use Filament\Forms;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Guava\Calendar\CalendarEvent;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\Filament\CalendarWidget;
use Filament\Forms\Components\DateTimePicker;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\Filament\Actions\CreateAction;
use App\Filament\Pages\WhatsappCenter;

class ReservationCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected ?string $locale = 'id';

    protected string|HtmlString|bool|null $heading = 'Kalender Reservasi';

    protected function getEventClickContextMenuActions(): array
    {
        return [
            $this->viewAction(),
            $this->editAction(),
            Action::make('change_status_pending')
                ->label('Ubah ke Pending')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Status')
                ->modalDescription(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $reservation = $reservationId ? Reservation::find($reservationId) : null;

                    if (!$reservation) {
                        return 'Reservasi tidak ditemukan';
                    }

                    return "Apakah Anda yakin ingin mengubah status dari '{$this->getStatusLabel($reservation->status)}' menjadi 'Pending'?";
                })
                ->modalSubmitActionLabel('Ya, Ubah Status')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $this->updateReservationStatus('pending', $reservationId);
                }),
            Action::make('change_status_confirmed')
                ->label('Ubah ke Dikonfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Status')
                ->modalDescription(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $reservation = $reservationId ? Reservation::find($reservationId) : null;

                    if (!$reservation) {
                        return 'Reservasi tidak ditemukan';
                    }

                    return "Apakah Anda yakin ingin mengubah status dari '{$this->getStatusLabel($reservation->status)}' menjadi 'Dikonfirmasi'?";
                })
                ->modalSubmitActionLabel('Ya, Ubah Status')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $this->updateReservationStatus('confirmed', $reservationId);
                }),
            Action::make('change_status_seated')
                ->label('Ubah ke Sudah Duduk')
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Status')
                ->modalDescription(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $reservation = $reservationId ? Reservation::find($reservationId) : null;

                    if (!$reservation) {
                        return 'Reservasi tidak ditemukan';
                    }

                    return "Apakah Anda yakin ingin mengubah status dari '{$this->getStatusLabel($reservation->status)}' menjadi 'Sudah Duduk'?";
                })
                ->modalSubmitActionLabel('Ya, Ubah Status')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $this->updateReservationStatus('seated', $reservationId);
                }),
            Action::make('change_status_completed')
                ->label('Ubah ke Selesai')
                ->icon('heroicon-o-check')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Status')
                ->modalDescription(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $reservation = $reservationId ? Reservation::find($reservationId) : null;

                    if (!$reservation) {
                        return 'Reservasi tidak ditemukan';
                    }

                    return "Apakah Anda yakin ingin mengubah status dari '{$this->getStatusLabel($reservation->status)}' menjadi 'Selesai'?";
                })
                ->modalSubmitActionLabel('Ya, Ubah Status')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $this->updateReservationStatus('completed', $reservationId);
                }),
            Action::make('pay_deposit')
                ->label('Bayar DP')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->visible(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $record = Reservation::find($reservationId);
                    return $record && in_array($record->status, ['pending', 'confirmed']);
                })
                ->schema([
                    Select::make('payment_method_id')
                        ->label('Metode Pembayaran')
                        ->options(\App\Models\PaymentMethod::pluck('name', 'id'))
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah DP')
                        ->numeric()
                        ->required()
                        ->prefix('Rp'),
                    Textarea::make('notes')
                        ->label('Catatan'),
                ])
                ->action(function (array $arguments, array $data) {
                    $reservationId = $this->extractReservationId($arguments);
                    $record = Reservation::find($reservationId);
                    if (!$record)
                        return;

                    $activeSession = \App\Models\CashSession::where('user_id', auth()->id())
                        ->where('status', 'open')
                        ->first();

                    if (!$activeSession) {
                        Notification::make()->title('Gagal')->body('Sesi kasir belum dibuka.')->danger()->send();
                        return;
                    }

                    $depositProduct = \App\Models\Product::firstOrCreate(
                        ['name' => 'Down Payment (DP)'],
                        [
                            'type' => 'service',
                            'unit_id' => \App\Models\Unit::where('name', 'Pcs')->first()?->id ?? \App\Models\Unit::first()?->id,
                            'is_sellable' => true,
                            'sell_price' => 0,
                        ]
                    );

                    $sale = \App\Models\Sale::create([
                        'reservation_id' => $record->id,
                        'order_type' => 'Dine In',
                        'customer_name' => $record->customer_name,
                        'payment_method_id' => $data['payment_method_id'],
                        'user_id' => auth()->id(),
                        'subtotal' => $data['amount'],
                        'tax' => 0,
                        'discount' => 0,
                        'total' => $data['amount'],
                        'final_total' => $data['amount'],
                        'cash_session_id' => $activeSession->id,
                        'status' => 'completed',
                        'is_paid' => true,
                        'paid_at' => now(),
                        'invoice_number' => 'DP-' . date('Ymd') . '-' . strtoupper(uniqid()),
                        'note' => 'DP: ' . $record->customer_name . ' (Res #' . $record->id . ') - ' . ($data['notes'] ?? ''),
                    ]);

                    \App\Models\SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $depositProduct->id,
                        'product_name' => 'Down Payment (DP)', // Snapshot name
                        'quantity' => 1,
                        'unit_price' => $data['amount'],
                        'subtotal' => $data['amount'],
                    ]);

                    $record->increment('deposit_amount', $data['amount']);
                    Notification::make()->title('Deposit Berhasil')->success()->send();
                    $this->refreshRecords();
                }),
            Action::make('change_status_cancelled')
                ->label('Ubah ke Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Perubahan Status')
                ->modalDescription(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $reservation = $reservationId ? Reservation::find($reservationId) : null;

                    if (!$reservation) {
                        return 'Reservasi tidak ditemukan';
                    }

                    return "Apakah Anda yakin ingin mengubah status dari '{$this->getStatusLabel($reservation->status)}' menjadi 'Dibatalkan'?";
                })
                ->modalSubmitActionLabel('Ya, Ubah Status')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $arguments) {
                    $reservationId = $this->extractReservationId($arguments);
                    $this->updateReservationStatus('cancelled', $reservationId);
                }),
        ];
    }

    /**
     * Update status reservation
     */
    private function updateReservationStatus(string $newStatus, ?int $reservationId): void
    {
        if (!$reservationId) {
            Notification::make()
                ->title('Error')
                ->body('Tidak ada reservasi yang dipilih')
                ->danger()
                ->send();
            return;
        }

        $reservation = Reservation::find($reservationId);

        if (!$reservation) {
            Notification::make()
                ->title('Error')
                ->body('Reservasi tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        $oldStatus = $reservation->status;
        $reservation->update(['status' => $newStatus]);

        Notification::make()
            ->title('Status berhasil diubah')
            ->body("Status berubah dari {$this->getStatusLabel($oldStatus)} ke {$this->getStatusLabel($newStatus)}")
            ->success()
            ->send();

        $this->refreshRecords();
    }

    /**
     * Ekstrak ID reservation dari arguments
     */
    private function extractReservationId(array $arguments): ?int
    {
        // Cek di extendedProps['key']
        if (isset($arguments['data']['event']['extendedProps']['key'])) {
            return (int) $arguments['data']['event']['extendedProps']['key'];
        }

        return null;
    }

    /**
     * Helper untuk mendapatkan label status
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'confirmed' => 'Dikonfirmasi',
            'seated' => 'Sudah Duduk',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $status,
        };
    }

    public function defaultSchema(Schema $schema): Schema
    {
        return \App\Filament\Resources\Reservations\Schemas\ReservationForm::configure($schema);
    }

    /**
     * Get events query
     */
    protected function getEvents(FetchInfo $info): Builder
    {
        return Reservation::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('reservation_date', [
                $info->start->startOfDay(),
                $info->end->endOfDay()
            ]);
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        $this->mountAction('createReservation', [
            'reservation_date' => $info->date->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Helper untuk membuat URL WhatsApp
     */
    private function getWhatsAppUrl(?string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $jid = $this->formatToJid($phoneNumber);
        return WhatsappCenter::getUrl(['jid' => $jid]);
    }

    private function formatToJid(?string $phoneNumber): ?string
    {
        if (empty($phoneNumber))
            return null;

        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (substr($phone, 0, 2) === '62') {
            $phone = $phone;
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '8') {
            $phone = '62' . $phone;
        }

        return $phone . '@s.whatsapp.net';
    }

    /**
     * OVERRIDE viewAction() untuk menentukan schema view
     */
    public function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->model(Reservation::class)
            ->modalHeading('Detail Reservasi')
            ->schema([
                Section::make('Informasi Customer')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->disabled(),
                        TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->disabled()
                            ->suffixActions([
                                Action::make('whatsapp_chat')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->iconButton()
                                    ->color('gray')
                                    ->tooltip('Chat WhatsApp')
                                    ->url(function ($get, $record) {
                                        $phoneNumber = $get('customer_phone') ?? $record?->customer_phone;
                                        return $this->getWhatsAppUrl($phoneNumber);
                                    })
                                    ->openUrlInNewTab(),

                                Action::make('confirm_wa')
                                    ->icon('heroicon-o-check-circle')
                                    ->iconButton()
                                    ->color('success')
                                    ->tooltip('Konfirmasi & Kirim WA')
                                    ->requiresConfirmation()
                                    ->modalHeading('Konfirmasi Reservasi')
                                    ->modalDescription('Status akan diubah menjadi "Confirmed" dan pesan konfirmasi WhatsApp akan dikirim ke pelanggan.')
                                    ->action(function ($record) {
                                        if (!$record)
                                            return;

                                        // Update Status
                                        $record->update(['status' => 'confirmed']);

                                        // Prepare WA Message
                                        $settings = app(\App\Settings\GeneralSettings::class);
                                        $template = $settings->wa_template_reservation_confirmation;

                                        $date = $record->reservation_date->translatedFormat('d F Y');
                                        $time = $record->reservation_date->format('H:i');

                                        // Format items for AI
                                        $preorderItems = $record->items->map(function ($item) {
                                            $note = $item->note ? " (Catatan: {$item->note})" : "";
                                            return "{$item->product->name} x{$item->quantity}{$note}";
                                        })->join(', ');

                                        try {
                                            $aiService = app(\App\Services\DeepSeekService::class);
                                            $message = $aiService->generateReservationConfirmation([
                                                'customer_name' => $record->customer_name,
                                                'date' => $date,
                                                'time' => $time,
                                                'guests' => $record->party_size,
                                                'preorder_items' => $preorderItems,
                                                'special_requests' => $record->special_requests,
                                            ], $template);
                                        } catch (\Exception $e) {
                                            $message = str_replace(
                                                ['{customer_name}', '{app_name}', '{date}', '{time}', '{guests}'],
                                                [$record->customer_name, $settings->app_name, $date, $time, $record->party_size],
                                                $template
                                            );
                                        }


                                        Notification::make()
                                            ->title('Reservasi Dikonfirmasi')
                                            ->success()
                                            ->send();

                                        $this->refreshRecords();

                                        $jid = $this->formatToJid($record->customer_phone);
                                        return redirect()->to(WhatsappCenter::getUrl([
                                            'jid' => $jid,
                                            'message' => $message
                                        ]));
                                    }),
                            ]),
                    ])->columns(2),

                Section::make('Detail Reservasi')
                    ->schema([
                        DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->disabled(),
                        TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('deposit_amount')
                            ->label('Down Payment (DP)')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'seated' => 'Seated',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),
                    ])->columns(3),

                Section::make('Pre-Order Menu')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->label('Menu')
                                    ->disabled(),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('note')
                                    ->label('Catatan')
                                    ->disabled(),
                            ])
                            ->columns(3)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ])
                    ->collapsed(),

                Section::make('Tambahan')
                    ->schema([
                        Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->disabled()
                            ->rows(3),
                    ]),
            ])
            ->extraModalFooterActions([
                Action::make('pay_deposit')
                    ->label('Bayar DP')
                    ->icon('heroicon-o-credit-card')
                    ->color('info')
                    ->visible(fn($record) => in_array($record?->status, ['pending', 'confirmed']))
                    ->schema([
                        Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->options(\App\Models\PaymentMethod::pluck('name', 'id'))
                            ->required(),
                        TextInput::make('amount')
                            ->label('Jumlah DP')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                        Textarea::make('notes')
                            ->label('Catatan'),
                    ])
                    ->action(function (array $data, Reservation $record) {
                        $activeSession = \App\Models\CashSession::where('user_id', auth()->id())
                            ->where('status', 'open')
                            ->first();

                        if (!$activeSession) {
                            Notification::make()->title('Gagal')->body('Sesi kasir belum dibuka.')->danger()->send();
                            return;
                        }

                        $depositProduct = \App\Models\Product::firstOrCreate(
                            ['name' => 'Down Payment (DP)'],
                            [
                                'type' => 'service',
                                'unit_id' => \App\Models\Unit::where('name', 'Pcs')->first()?->id ?? \App\Models\Unit::first()?->id,
                                'is_sellable' => true,
                                'sell_price' => 0,
                            ]
                        );

                        $sale = \App\Models\Sale::create([
                            'reservation_id' => $record->id,
                            'order_type' => 'Dine In',
                            'customer_name' => $record->customer_name,
                            'payment_method_id' => $data['payment_method_id'],
                            'user_id' => auth()->id(),
                            'subtotal' => $data['amount'],
                            'tax' => 0,
                            'discount' => 0,
                            'total' => $data['amount'],
                            'final_total' => $data['amount'],
                            'cash_session_id' => $activeSession->id,
                            'status' => 'completed',
                            'is_paid' => true,
                            'paid_at' => now(),
                            'invoice_number' => 'DP-' . date('Ymd') . '-' . strtoupper(uniqid()),
                            'note' => 'DP: ' . $record->customer_name . ' (Res #' . $record->id . ') - ' . ($data['notes'] ?? ''),
                        ]);

                        \App\Models\SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $depositProduct->id,
                            'product_name' => 'Down Payment (DP)', // Snapshot name
                            'quantity' => 1,
                            'unit_price' => $data['amount'],
                            'subtotal' => $data['amount'],
                        ]);

                        $record->increment('deposit_amount', $data['amount']);
                        Notification::make()->title('Deposit Berhasil')->success()->send();
                        $this->refreshRecords();
                    }),
                Action::make('convert_to_sale')
                    ->label('Proses ke Kasir')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->visible(fn($record) => in_array($record?->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->modalHeading('Konversi ke Transaksi POS')
                    ->modalDescription('Reservasi ini akan dikonversi menjadi transaksi aktif.')
                    ->action(function (Reservation $record) {
                        // 0. Check Active Session
                        $activeSession = \App\Models\CashSession::where('user_id', auth()->id())
                            ->where('status', 'open')
                            ->first();

                        if (!$activeSession) {
                            Notification::make()->title('Gagal')->body('Sesi kasir belum dibuka.')->danger()->send();
                            return;
                        }

                        // 1. Create Sale Header
                        $sale = \App\Models\Sale::create([
                            'reservation_id' => $record->id,
                            'user_id' => auth()->id(),
                            'cash_session_id' => $activeSession->id,
                            'invoice_number' => 'RSVP-' . date('Ymd') . '-' . strtoupper(uniqid()),
                            'customer_name' => $record->customer_name,
                            'status' => 'draft',
                            'subtotal' => 0,
                            'tax' => 0,
                            'discount' => 0,
                            'final_total' => 0,
                            'total' => 0,
                            'order_type' => 'Dine In',
                        ]);

                        // 2. Copy Items (Snapshot Name)
                        $itemsSubtotal = 0;
                        foreach ($record->items as $item) {
                            $product = $item->product;
                            if (!$product)
                                continue;

                            $price = $item->unit_price;
                            $subtotal = $price * $item->quantity;

                            $sale->items()->create([
                                'product_id' => $item->product_id,
                                'product_name' => $product->name, // Snapshot name
                                'quantity' => $item->quantity,
                                'unit_price' => $price,
                                'subtotal' => $subtotal,
                                'notes' => $item->note,
                            ]);

                            $itemsSubtotal += $subtotal;
                        }

                        // 3. Handle Deposit Deduction
                        if ($record->deposit_amount > 0) {
                            $sale->items()->create([
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
                            'total' => $itemsSubtotal,
                            'final_total' => $itemsSubtotal,
                        ]);

                        // 5. Update Reservation Status
                        $record->update(['status' => 'seated']);

                        Notification::make()
                            ->title('Transaksi Berhasil Dibuat')
                            ->body("Sale #{$sale->invoice_number} telah dibuat.")
                            ->success()
                            ->send();

                        return redirect()->to('/admin/sales/' . $sale->id . '/edit');
                    }),
            ]);
    }

    /**
     * OVERRIDE editAction() untuk menentukan schema edit
     */
    public function editAction(): EditAction
    {
        return EditAction::make()
            ->modalHeading('Edit Reservasi')
            ->model(Reservation::class)
            ->schema([
                Section::make('Informasi Customer')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->required(),
                    ])->columns(2),

                Section::make('Detail Reservasi')
                    ->schema([
                        DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->required()
                            ->seconds(false),
                        TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('deposit_amount')
                            ->label('Down Payment (DP)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'seated' => 'Seated',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ])->columns(3),

                Section::make('Pre-Order Menu')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name', fn(Builder $query) => $query->where('is_sellable', true))
                                    ->label('Menu')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $product = \App\Models\Product::find($state);
                                        $price = $product?->sell_price ?? $product?->price ?? 0;
                                        $set('unit_price', $price);
                                        $set('total_price', $price * (int) ($get('quantity') ?? 1));
                                    }),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $price = (float) $get('unit_price');
                                        $set('total_price', $price * (int) $state);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Harga')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('note')
                                    ->label('Catatan'),
                            ])
                            ->columns(3)
                            ->collapsed(),
                    ])
                    ->collapsed(),

                Section::make('Tambahan')
                    ->schema([
                        Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->rows(3),
                    ]),
            ]);
    }

    /**
     * Action untuk create reservation baru
     */
    public function createReservationAction(): CreateAction
    {
        return CreateAction::make('createReservation')
            ->modalHeading('Buat Reservasi Baru')
            ->model(Reservation::class)
            ->schema([
                Section::make('Informasi Customer')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->required()
                            ->tel(),
                    ])->columns(2),

                Section::make('Detail Reservasi')
                    ->schema([
                        DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->required()
                            ->seconds(false),
                        TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->required()
                            ->required()
                            ->minValue(1),
                    ])->columns(2),

                Section::make('Pre-Order Menu')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name', fn(Builder $query) => $query->where('is_sellable', true))
                                    ->label('Menu')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $product = \App\Models\Product::find($state);
                                        $price = $product?->sell_price ?? $product?->price ?? 0;
                                        $set('unit_price', $price);
                                        $set('total_price', $price * (int) ($get('quantity') ?? 1));
                                    }),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $price = (float) $get('unit_price');
                                        $set('total_price', $price * (int) $state);
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Harga')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->prefix('Rp'),
                                TextInput::make('note')
                                    ->label('Catatan'),
                            ])
                            ->columns(3)
                            ->collapsed(),
                    ])
                    ->collapsed(),

                Section::make('Tambahan')
                    ->schema([
                        Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->rows(3),
                    ]),
            ])
            ->fillForm(function (array $arguments) {
                return [
                    'reservation_date' => $arguments['reservation_date'] ?? null,
                ];
            })
            ->createAnother(false);
    }

    /**
     * Set tinggi calendar
     */
    protected function getCalendarHeight(): string
    {
        return '600px'; // Tambah tinggi untuk tampilan lebih baik
    }

    /**
     * Business hours restoran
     */
    protected function getBusinessHours(): array
    {
        return [
            [
                'daysOfWeek' => [1, 2, 3, 4, 5, 6, 0],
                'startTime' => '10:00',
                'endTime' => '22:00',
            ]
        ];
    }

    /**
     * Slot duration 15 menit
     */
    protected function getSlotDuration(): string
    {
        return '00:15:00';
    }

    /**
     * Waktu mulai
     */
    protected function getSlotMinTime(): string
    {
        return '10:00:00';
    }

    /**
     * Waktu berakhir
     */
    protected function getSlotMaxTime(): string
    {
        return '22:00:00';
    }
}
