<?php

namespace App\Filament\Resources\Reservations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;

class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pelanggan')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('customer_phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),
                            
                        TextInput::make('party_size')
                            ->label('Jumlah Orang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(50),
                            
                        Textarea::make('special_requests')
                            ->label('Permintaan Khusus')
                            ->rows(3),
                    ])->columnSpanFull(),
                    
                Section::make('Detail Reservasi')
                    ->schema([
                        DateTimePicker::make('reservation_date')
                            ->label('Tanggal & Jam Reservasi')
                            ->required()
                            ->native(false)
                            ->minutesStep(15)
                            ->displayFormat('d/m/Y H:i')
                            ->weekStartsOnMonday(),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Dikonfirmasi',
                                'seated' => 'Sudah Duduk',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columnSpanFull(),
            ]);
    }
}
