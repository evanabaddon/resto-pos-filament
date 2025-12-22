<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255),
            PhoneInput::make('phone')
                ->label('Nomor HP')
                ->required()
                ->unique(ignoreRecord: true)
                ->defaultCountry('ID')
                ->inputNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                ->dehydrateStateUsing(fn($state) => (string) str($state)->replace('+', ''))
                ->required(),
            TextInput::make('email')
                ->email()
                ->maxLength(255),
            Select::make('tier_id')
                ->label('Level')
                ->relationship('tier', 'name')
                ->default(1)
                ->required(),
        ]);
    }
}