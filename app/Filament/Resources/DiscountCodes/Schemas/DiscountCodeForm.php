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
                Section::make('Informasi Utama')
                    ->description('Data utama untuk kode diskon yang digunakan di POS.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Diskon')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Kode yang dimasukkan pelanggan di POS'),

                        TextInput::make('name')
                            ->label('Nama Promo')
                            ->required(),

                        Select::make('type')
                            ->label('Jenis Diskon')
                            ->options([
                                'percentage' => 'Persentase (%)',
                                'fixed' => 'Nominal (Rp)',
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('value')
                            ->label('Nilai Diskon')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Syarat & Batasan')
                    ->description('Atur batas dan ketentuan penggunaan kode diskon.')
                    ->schema([
                        TextInput::make('min_purchase')
                            ->label('Min. Pembelian')
                            ->numeric()
                            ->helperText('Opsional — transaksi minimal agar diskon aktif'),

                        TextInput::make('max_discount')
                            ->label('Maks. Diskon')
                            ->numeric()
                            ->helperText('Opsional — batas maksimum nilai diskon'),

                        TextInput::make('usage_limit')
                            ->label('Batas Pemakaian')
                            ->numeric()
                            ->helperText('Kosongkan jika tanpa batas'),
                    ])
                    ->columns(3),

                Section::make('Periode Berlaku')
                    ->description('Waktu aktif dan status dari promo.')
                    ->schema([
                        DatePicker::make('valid_from')
                            ->label('Berlaku Dari'),

                        DatePicker::make('valid_until')
                            ->label('Berlaku Sampai'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
