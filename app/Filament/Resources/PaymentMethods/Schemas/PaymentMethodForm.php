<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.payment_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.method_name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('code')
                            ->label(__('messages.payment_code'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->readOnly(fn($record) => $record?->code === 'cash')
                            ->helperText(__('messages.payment_code_helper')),

                        TextInput::make('account_category')
                            ->label(__('messages.account_category') ?? 'Kategori Akun/Rekening')
                            ->helperText('Contoh: Rekening A, Rekening B, Kas & Petty Cash. Biarkan kosong jika tidak ingin dikelompokkan.')
                            ->maxLength(100),

                        Toggle::make('is_active')
                            ->label(__('messages.is_active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
