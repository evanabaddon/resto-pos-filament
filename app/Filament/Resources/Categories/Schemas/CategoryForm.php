<?php

namespace App\Filament\Resources\Categories\Schemas;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $operation) {
                        if ($operation === 'create' || $operation === 'edit') {
                            $set('slug', Str::slug($state));
                        }
                    }),
                
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique('categories', 'slug', ignoreRecord: true)
                    ->rules(['alpha_dash']),
                
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
