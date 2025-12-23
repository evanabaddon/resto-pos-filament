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
                TextInput::make('name')->required(),
                TextInput::make('email')->required(),
                Select::make('role')
                    ->options(\App\Enums\UserRole::class)
                    ->label('Role')
                    ->searchable(),
                TextInput::make('password')
                    ->password()
                    ->label('Password'),
            ]);
    }
}
