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
                Section::make('Module Activation')
                    ->description('Aktifkan modul tambahan untuk fitur perencanaan fiskal & randomizer.')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('enable_fiscal_planning')
                            ->label('Aktifkan Modul Perencanaan Fiskal')
                            ->helperText('Jika aktif, Anda dapat mengatur target omzet harian dan menggunakan randomizer generator.')
                            ->live(),

                        TextInput::make('fiscal_license_key')
                            ->label('License Key')
                            ->visible(fn($get) => $get('enable_fiscal_planning'))
                            ->required(fn($get) => $get('enable_fiscal_planning'))
                            ->placeholder('FISCAL-PRO-XXXX-XXXX')
                            ->rules([
                                fn($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($get('enable_fiscal_planning') && !str_starts_with($value ?? '', 'FISCAL-PRO-')) {
                                        $fail('License key tidak valid. Format harus: FISCAL-PRO-XXXX');
                                    }
                                },
                            ]),
                    ]),

                Section::make('Template Excel Pemerintah')
                    ->description('Upload file template excel kosong dari dinas pajak di sini.')
                    ->visible(fn(\App\Settings\FiscalSettings $settings) => $settings->enable_fiscal_planning) // Hide if module disabled? Or maybe keep template export available but just data is real?
                    // User said: "report all daily turnover according to real transactions" for normal condition. 
                    // But Template Export is part of the convenience. 
                    // Let's decide: Settings for template should probably be visible always, or maybe just hidden to simplify?
                    // "buat agar randomize dan penentuan omzet parameter itu sbgai modul tambahan"
                    // So Template export ITSELF might be useful for real data too.
                    // Let's Keep Template settings visible always, but changing the description or logic in Report page.
                    // Wait, usually paid module includes the convenience.
                    // Let's HIDE the planning/randomizer related settings only?
                    // Actually, the "FiscalSettingsPage" IS about the template mostly.
                    // Let's just Add the toggle at the top.
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
