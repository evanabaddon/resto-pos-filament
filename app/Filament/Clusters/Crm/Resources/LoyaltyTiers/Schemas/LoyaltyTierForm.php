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
                    ->label('Nama Level')
                    ->required()
                    ->maxLength(255),
                Grid::make(2)
                    ->schema([
                        TextInput::make('min_points')
                            ->label('Min. Poin')
                            ->numeric()
                            ->default(0)
                            ->mask('999999'),
                        TextInput::make('min_visits')
                            ->label('Min. Kunjungan')
                            ->numeric()
                            ->default(0),
                    ]),
                Textarea::make('benefit_description')
                    ->label('Deskripsi Keuntungan')
                    ->columnSpanFull(),
            ]);
    }
}
