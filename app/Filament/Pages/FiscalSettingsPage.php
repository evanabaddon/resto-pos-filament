<?php

namespace App\Filament\Pages;

use App\Settings\FiscalSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;

class FiscalSettingsPage extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Pengaturan Pajak';

    protected static ?string $title = 'Pengaturan Pajak (Template Excel)';

    protected static string $settings = FiscalSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Template Excel Pemerintah')
                    ->description('Upload file template excel kosong dari dinas pajak di sini.')
                    ->schema([
                        FileUpload::make('template_path')
                            ->label('File Template (Excel)')
                            ->helperText('Upload file .xlsx kosong.')
                            ->disk('public')
                            ->directory('fiscal-templates')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required(),
                    ]),

                Section::make('Mapping Kolom & Baris')
                    ->description('Sesuaikan posisi data dengan template yang diupload.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('start_row')
                            ->label('Baris Awal Data')
                            ->numeric()
                            ->default(5)
                            ->required()
                            ->helperText('Nomor baris pertama dimana data akan mulai ditulis (misal: 5).'),

                        TextInput::make('date_column')
                            ->label('Kolom Tanggal')
                            ->default('A')
                            ->required()
                            ->placeholder('A')
                            ->helperText('Huruf kolom untuk Tanggal.'),

                        TextInput::make('amount_column')
                            ->label('Kolom Omzet (Bruto)')
                            ->default('C')
                            ->required()
                            ->placeholder('C')
                            ->helperText('Huruf kolom untuk Total Omzet.'),

                        TextInput::make('tax_column')
                            ->label('Kolom Pajak')
                            ->default('D')
                            ->required()
                            ->placeholder('D')
                            ->helperText('Huruf kolom untuk Nilai Pajak.'),
                    ]),
            ]);
    }
}
