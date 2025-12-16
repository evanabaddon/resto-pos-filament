<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use UnitEnum;
use App\Settings\GeneralSettings;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class AppSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'General Settings';

    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->columnSpanFull()
                    ->tabs([
                        // TAB: GENERAL
                        Tab::make('General')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Identitas Aplikasi')
                                            ->columnSpan(1)
                                            ->schema([
                                                TextInput::make('app_name')
                                                    ->label('App Name')
                                                    ->required()
                                                    ->placeholder('Nama Restoran Anda'),

                                                FileUpload::make('app_logo')
                                                    ->label('Logo')
                                                    ->image()
                                                    ->disk('public') // Ensure public visibility
                                                    ->directory('settings/logo')
                                                    ->maxSize(2048),

                                                FileUpload::make('app_favicon')
                                                    ->label('Favicon')
                                                    ->image()
                                                    ->disk('public') // Ensure public visibility
                                                    ->directory('settings/favicon')
                                                    ->maxSize(512),
                                            ]),

                                        Section::make('Informasi Perusahaan (Untuk Laporan/Struk)')
                                            ->columnSpan(1)
                                            ->schema([
                                                Textarea::make('company_address')
                                                    ->label('Alamat Perusahaan')
                                                    ->rows(3),
                                                TextInput::make('company_phone')
                                                    ->label('Telepon'),
                                                TextInput::make('company_email')
                                                    ->label('Email')
                                                    ->email(),
                                                TextInput::make('app_website')
                                                    ->label('Website')
                                                    ->url(),
                                            ]),

                                        Section::make('Konfigurasi POS')
                                            ->columnSpan(1)
                                            ->schema([
                                                Toggle::make('enable_table_number')
                                                    ->label('Aktifkan Nomor Meja')
                                                    ->helperText('Tampilkan input nomor meja saat transaksi Dine In'),
                                                Select::make('printer_width')
                                                    ->label('Ukuran Printer')
                                                    ->options([
                                                        '58mm' => '58mm (Standard)',
                                                        '80mm' => '80mm (Large)',
                                                    ])
                                                    ->required()
                                                    ->default('58mm'),
                                            ]),

                                        Section::make('Sosial Media')
                                            ->columnSpan(2)
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('app_instagram')
                                                            ->label('Instagram')
                                                            ->prefix('instagram.com/'),
                                                        TextInput::make('app_tiktok')
                                                            ->label('TikTok')
                                                            ->prefix('tiktok.com/@'),
                                                    ])
                                            ])
                                    ])
                            ]),

                        // TAB: MODULES
                        Tab::make('Modules')
                            ->schema([
                                Section::make('HRM (Human Resource Management)')
                                    ->description('Manage Employees, Attendance, and Payroll.')
                                    ->schema([
                                        TextInput::make('hrm_license_key')
                                            ->label('License Key')
                                            ->password() // Hide characters
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: HRM-PRO-XXXX')
                                            ->live(onBlur: true) // Validate on blur
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                // Simple Logic: Key must start with HRM-PRO-
                                                if ($state && str_starts_with($state, 'HRM-PRO-')) {
                                                    // Valid
                                                } else {
                                                    // Invalid: Force disable toggle
                                                    $set('enable_hrm', false);
                                                }
                                            }),

                                        Toggle::make('enable_hrm')
                                            ->label('Enable HRM Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('hrm_license_key') ?? '', 'HRM-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('hrm_license_key') ?? '', 'HRM-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul SDM.'
                                            ),
                                    ])
                            ])
                    ])

            ]);
    }
}
