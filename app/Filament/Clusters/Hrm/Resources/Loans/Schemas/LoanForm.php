<?php

namespace App\Filament\Clusters\Hrm\Resources\Loans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pinjaman')
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Pegawai'),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Jumlah Pinjaman'),
                        TextInput::make('installment_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Cicilan Per Bulan'),
                        TextInput::make('start_month_year')
                            ->required()
                            ->placeholder('YYYY-MM')
                            ->regex('/^\d{4}-\d{2}$/')
                            ->helperText('Format: YYYY-MM (Contoh: 2024-01)')
                            ->label('Mulai Potong Gaji'),
                        Textarea::make('reason')
                            ->rows(3)
                            ->label('Alasan'),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'paid' => 'Lunas',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2)
                    ->columnSpan('full'),
            ]);
    }
}
