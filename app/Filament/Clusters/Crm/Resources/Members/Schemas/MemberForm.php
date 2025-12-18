<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Nomor HP')
                ->tel()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
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