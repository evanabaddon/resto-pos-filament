<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('messages.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('messages.email'))
                    ->required(),
                Select::make('role')
                    ->options(\App\Enums\UserRole::class)
                    ->label(__('messages.role'))
                    ->searchable(),
                TextInput::make('password')
                    ->password()
                    ->label(__('messages.password')),
            ]);
    }
}
