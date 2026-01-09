<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('messages.name'))->required(),
                TextInput::make('symbol')->label(__('messages.symbol')),
                Select::make('base_unit_id')
                    ->relationship('baseUnit', 'name')
                    ->label(__('messages.base_unit'))
                    ->searchable(),
                TextInput::make('conversion_rate')
                    ->numeric()
                    ->label(__('messages.conversion_rate')),
            ]);
    }
}
