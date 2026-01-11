<?php

namespace App\Filament\Resources\DiscountCodes\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

class DiscountCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.discount_info'))
                    ->description(__('messages.discount_info_desc'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('messages.discount_code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText(__('messages.discount_code_helper')),

                        TextInput::make('name')
                            ->label(__('messages.promo_name'))
                            ->required(),

                        Select::make('type')
                            ->label(__('messages.discount_type'))
                            ->options([
                                'percentage' => __('messages.percentage'),
                                'fixed' => __('messages.fixed_amount'),
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('value')
                            ->label(__('messages.discount_value'))
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('messages.conditions_limits'))
                    ->description(__('messages.conditions_limits_desc'))
                    ->schema([
                        TextInput::make('min_purchase')
                            ->label(__('messages.min_purchase'))
                            ->numeric()
                            ->helperText(__('messages.min_purchase_helper')),

                        TextInput::make('max_discount')
                            ->label(__('messages.max_discount'))
                            ->numeric()
                            ->helperText(__('messages.max_discount_helper')),

                        TextInput::make('usage_limit')
                            ->label(__('messages.usage_limit'))
                            ->numeric()
                            ->helperText(__('messages.usage_limit_helper')),
                    ])
                    ->columns(3),

                Section::make(__('messages.validity_period'))
                    ->description(__('messages.validity_period_desc'))
                    ->schema([
                        DatePicker::make('valid_from')
                            ->label(__('messages.valid_from')),

                        DatePicker::make('valid_until')
                            ->label(__('messages.valid_until')),

                        Toggle::make('is_active')
                            ->label(__('messages.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
