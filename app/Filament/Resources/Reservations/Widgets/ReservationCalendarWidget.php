<?php

namespace App\Filament\Resources\Reservations\Widgets;

use Filament\Forms;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Guava\Calendar\CalendarEvent;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
        return $schema->components([
            Section::make('Informasi Customer')
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Nama Customer')
                        ->disabled(),
                    TextInput::make('customer_phone')
                        ->label('Telepon')
                        ->disabled(),
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

            Section::make('Tambahan')
                ->schema([
                    Textarea::make('special_requests')
                        ->label('Permintaan Khusus')
                        ->disabled()
                        ->rows(3),
                ]),
        ]);
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

        // Format nomor untuk WhatsApp
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (substr($phone, 0, 2) === '62') {
            $whatsappNumber = $phone;
        } elseif (substr($phone, 0, 1) === '0') {
            $whatsappNumber = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '8') {
            $whatsappNumber = '62' . $phone;
        } else {
            $whatsappNumber = $phone;
        }

        return 'https://wa.me/' . $whatsappNumber;
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
                                        $time = $record->reservation_date->format('H:i'); // Use reservation_date, checked logic
                            
                                        $message = str_replace(
                                            ['{customer_name}', '{app_name}', '{date}', '{time}', '{guests}'],
                                            [$record->customer_name, $settings->app_name, $date, $time, $record->party_size],
                                            $template
                                        );

                                        // Build URL
                                        $phone = preg_replace('/[^0-9]/', '', $record->customer_phone);
                                        if (substr($phone, 0, 1) == '0')
                                            $phone = '62' . substr($phone, 1);

                                        // Use api.whatsapp.com directly to avoid encoding issues with wa.me redirects
                                        $url = "https://api.whatsapp.com/send?phone={$phone}&text=" . rawurlencode($message);

                                        Notification::make()
                                            ->title('Reservasi Dikonfirmasi')
                                            ->success()
                                            ->send();

                                        // Refresh Calendar
                                        $this->refreshRecords();

                                        // Redirect via Action return if inside a modal handling context
                                        // For suffixAction inside a ViewAction, we might need to rely on the user clicking the link? 
                                        // But action() handles server side.
                                        // To open URL, we return a redirect action or use $this->redirect().
                                        redirect()->away($url);
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
                Action::make('convert_to_sale')
                    ->label('Convert to Sale (POS)')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Konversi ke Transaksi POS')
                    ->modalDescription('Reservasi ini akan dikonversi menjadi transaksi aktif. Stok akan dipotong saat pembayaran.')
                    ->action(function (Reservation $record) {
                        // 1. Create Sale Header
                        $sale = \App\Models\Sale::create([
                            'reservation_id' => $record->id,
                            'customer_name' => $record->customer_name, // Fallback if no member
                            'status' => 'pending',
                            'total_amount' => 0, // Will be calculated
                            'payment_method_id' => null,
                        ]);

                        // 2. Copy Items
                        $total = 0;
                        foreach ($record->items as $item) {
                            $product = $item->product;
                            if (!$product)
                                continue;

                            $price = $product->price;
                            $subtotal = $price * $item->quantity;

                            $sale->items()->create([
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity,
                                'unit_price' => $price,
                                'subtotal' => $subtotal,
                                'note' => $item->note,
                            ]);

                            $total += $subtotal;
                        }

                        // 3. Update Sale Total
                        $sale->update(['total_amount' => $total]);

                        // 4. Update Reservation Status
                        $record->update(['status' => 'seated']);

                        Notification::make()
                            ->title('Transaksi Berhasil Dibuat')
                            ->body("Sale #{$sale->id} telah dibuat dari reservasi ini.")
                            ->success()
                            ->send();

                        // 5. Redirect to POS (Optional: needs logical routing to open POS with this sale)
                        // For now we just notify. Ideally we redirect to a POS URL with ?sale_id=X
                        // But POS uses Livewire state. 
                        // Alternative: Redirect to Sale Edit page (Backoffice) or simple notification.
                        // Let's redirect to Sales Edit for now as it's safer.
                        // OR: Just keep it simple as notification.
            
                        // We will just show notification for now as POS integration is complex state-wise.
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
                            ->minValue(1),
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
                                    ->relationship('product', 'name')
                                    ->label('Menu')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
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
                                    ->relationship('product', 'name')
                                    ->label('Menu')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
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