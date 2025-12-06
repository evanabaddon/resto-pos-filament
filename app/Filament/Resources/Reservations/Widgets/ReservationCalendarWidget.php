<?php

namespace App\Filament\Resources\Reservations\Widgets;

use Filament\Forms;
use App\Models\Reservation;
use Filament\Schemas\Schema;
use Guava\Calendar\CalendarEvent;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\Filament\CalendarWidget;
use Filament\Forms\Components\DateTimePicker;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\ViewAction;
use Guava\Calendar\Filament\Actions\CreateAction;

class ReservationCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected ?string $locale = 'id';

    protected string | HtmlString | bool | null $heading = 'Kalender Reservasi';

    protected function getEventClickContextMenuActions(): array
    {
        return [
            $this->viewAction(),
            $this->editAction(),
        ];
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
     * OVERRIDE viewAction() untuk menentukan schema view
     */
    public function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->model(Reservation::class)
            ->schema([
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
     * OVERRIDE editAction() untuk menentukan schema edit
     */
    public function editAction(): EditAction
    {
        return EditAction::make()
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
                            ->minValue(1)
                            ->maxValue(20),
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
                    ])->columns(2),
                
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