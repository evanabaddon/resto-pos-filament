<?php

namespace App\Filament\Resources\Reservations\Widgets;

use App\Models\Reservation;
use App\Models\Table; // Jika ada relasi dengan table
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\CalendarEvent;

class ReservationCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;
    
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
    
    /**
     * Konfigurasi event kalender - UNTUK CLICK ACTION
     */
    protected function getEventConfiguration(): array
    {
        return [
            CalendarEvent::make()
                ->action('view') // default action saat diklik
                ->actions([ // multiple actions jika perlu
                    'view',
                    'edit',
                ]),
        ];
    }
    
    /**
     * Action untuk melihat detail reservation
     */
    public function viewReservationAction(): ViewAction
    {
        return ViewAction::make('viewReservation')
            ->model(Reservation::class)
            ->form([
                Forms\Components\Section::make('Informasi Customer')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->disabled(),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Detail Reservasi')
                    ->schema([
                        Forms\Components\DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->disabled(),
                        Forms\Components\TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Select::make('status')
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
                
                Forms\Components\Section::make('Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->disabled()
                            ->rows(3),
                    ]),
            ]);
    }
    
    /**
     * Action untuk edit reservation
     */
    public function editReservationAction(): EditAction
    {
        return EditAction::make('editReservation')
            ->model(Reservation::class)
            ->form([
                Forms\Components\Section::make('Informasi Customer')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->required()
                            ->tel(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Detail Reservasi')
                    ->schema([
                        Forms\Components\DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(20),
                        Forms\Components\Select::make('status')
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
                
                Forms\Components\Section::make('Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests')
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
            ->model(Reservation::class)
            ->form([
                Forms\Components\Section::make('Informasi Customer')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama Customer')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Telepon')
                            ->required()
                            ->tel(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Detail Reservasi')
                    ->schema([
                        Forms\Components\DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Waktu Reservasi')
                            ->required()
                            ->seconds(false)
                            ->default(now()->addHour()), // default 1 jam dari sekarang
                        Forms\Components\TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(2),
                    ])->columns(2),
                
                Forms\Components\Section::make('Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->rows(3),
                    ]),
            ])
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
    
    /**
     * HEADER TOOLBAR dengan button Create
     */
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         \Filament\Actions\Action::make('create')
    //             ->label('Reservasi Baru')
    //             ->icon('heroicon-o-plus')
    //             ->action(function() {
    //                 $this->mountAction('createReservation');
    //             }),
    //     ];
    // }
}