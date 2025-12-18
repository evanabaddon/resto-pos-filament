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
                Section::make('Detail Hadiah')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Hadiah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('points_required')
                            ->label('Poin Dibutuhkan')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Select::make('product_id')
                            ->label('Produk (Optional)')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Jika diisi, penukaran akan otomatis menambahkan produk ini ke keranjang (Diskon 100%).'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
