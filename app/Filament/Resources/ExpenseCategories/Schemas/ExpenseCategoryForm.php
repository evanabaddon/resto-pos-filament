<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.category_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.category_name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label(__('messages.description'))
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label(__('messages.is_active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
