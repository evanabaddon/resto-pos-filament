<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyTiers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LoyaltyTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('messages.tier_name'))
                    ->required()
                    ->maxLength(255),
                Grid::make(2)
                    ->schema([
                        TextInput::make('min_points')
                            ->label(__('messages.min_points'))
                            ->numeric()
                            ->default(0)
                            ->mask('999999'),
                        TextInput::make('min_visits')
                            ->label(__('messages.min_visits'))
                            ->numeric()
                            ->default(0),
                    ]),
                Textarea::make('benefit_description')
                    ->label(__('messages.benefit_desc'))
                    ->columnSpanFull(),
            ]);
    }
}
