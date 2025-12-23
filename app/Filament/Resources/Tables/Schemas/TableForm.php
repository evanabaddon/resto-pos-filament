<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class TableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Meja')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Meja')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        TextInput::make('slug')
                            ->label('Unique Slug / QR Code Link')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('status')
                            ->options([
                                'available' => 'Tersedia',
                                'occupied' => 'Terisi',
                                'reserved' => 'Reservasi',
                            ])
                            ->default('available')
                            ->required(),
                    ])
            ]);
    }
}
