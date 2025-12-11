<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengeluaran')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        
                        TextInput::make('reference')
                            ->label('Referensi')
                            ->default(fn() => Expense::generateReference())
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),
                    ])
                    ->columns(2),

                Section::make('Sumber Dana & Penerima')
                    ->schema([
                        Select::make('fund_source')
                            ->label('Sumber Dana')
                            ->required()
                            ->options(Expense::getFundSources())
                            ->default(Expense::FUND_SOURCE_CASHIER)
                            ->reactive(),
                        
                        TextInput::make('recipient')
                            ->label('Penerima')
                            ->maxLength(255)
                            ->placeholder('Nama penerima pembayaran')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Detail Pengeluaran')
                    ->schema([
                        Select::make('expense_category_id')
                            ->label('Kategori')
                            ->required()
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->maxLength(500),
                            ]),
                        
                        Textarea::make('description')
                            ->label('Deskripsi Pengeluaran')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        
                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000),
                    ])->columnSpanFull(),
                
                Section::make('Status & Catatan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->searchable()
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->reactive(),
                        
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Catatan tambahan...')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}