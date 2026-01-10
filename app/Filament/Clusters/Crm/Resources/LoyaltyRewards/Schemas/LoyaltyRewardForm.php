<?php

namespace App\Filament\Clusters\Crm\Resources\LoyaltyRewards\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoyaltyRewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.details')) // Generic details key or just remove/leave? Let's use 'Reward Details' concept. I'll just use literal or find key. I used 'details' key before? No. Creating new or using generic. Using keys.
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.reward_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('points_required')
                            ->label(__('messages.points_required'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Select::make('product_id')
                            ->label(__('messages.related_product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('messages.related_product_helper')),
                        Toggle::make('is_active')
                            ->label(__('messages.is_active'))
                            ->default(true),
                        Textarea::make('description')
                            ->label(__('messages.description'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
