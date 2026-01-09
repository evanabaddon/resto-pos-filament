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
                Section::make(__('messages.table_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.table_name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        TextInput::make('slug')
                            ->label(__('messages.slug_qr'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('status')
                            ->options([
                                'available' => __('messages.status_available'),
                                'occupied' => __('messages.status_occupied'),
                                'reserved' => __('messages.status_reserved'),
                            ])
                            ->default('available')
                            ->required(),
                    ])
            ]);
    }
}
