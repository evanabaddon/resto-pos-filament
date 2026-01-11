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
                Section::make(__('messages.customer_info'))
                    ->schema([
                        TextInput::make('customer_name')
                            ->label(__('messages.customer_name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('customer_phone')
                            ->label(__('messages.customer_phone'))
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('party_size')
                            ->label(__('messages.party_size'))
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Textarea::make('special_requests')
                            ->label(__('messages.special_requests'))
                            ->rows(3),
                    ])->columnSpanFull(),

                Section::make(__('messages.reservation_details'))
                    ->schema([
                        DateTimePicker::make('reservation_date')
                            ->label(__('messages.reservation_date'))
                            ->required()
                            ->native(false)
                            ->minutesStep(15)
                            ->displayFormat('d/m/Y H:i')
                            ->weekStartsOnMonday(),

                        TextInput::make('deposit_amount')
                            ->label(__('messages.deposit_amount'))
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->helperText(__('messages.deposit_amount_helper')),

                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'pending' => __('messages.status_pending'),
                                'confirmed' => __('messages.status_confirmed'),
                                'seated' => __('messages.status_seated'),
                                'completed' => __('messages.status_completed'),
                                'cancelled' => __('messages.status_cancelled'),
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columnSpanFull(),
            ]);
    }
}
