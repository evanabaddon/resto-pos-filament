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
                            ->icon('heroicon-o-cog-8-tooth')
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

                        // TAB: FISCAL
                        Tab::make('Fiscal / Pajak')
                            ->visible(fn() => app(GeneralSettings::class)->enable_fiscal_planning)
                            ->icon('heroicon-o-calculator')
                            ->schema([

                                Section::make('Excel Template Configuration')
                                    ->description('Konfigurasi template laporan pajak (Excel).')
                                    ->schema([
                                        FileUpload::make('template_path')
                                            ->label('File Template (Excel)')
                                            ->disk('public')
                                            ->directory('fiscal-templates')
                                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('start_row')
                                                    ->label('Baris Mulai Data')
                                                    ->numeric()
                                                    ->default(2)
                                                    ->required(),
                                                TextInput::make('date_column')
                                                    ->label('Kolom Tanggal')
                                                    ->default('A')
                                                    ->required(),
                                                TextInput::make('amount_column')
                                                    ->label('Kolom Omzet (Total)')
                                                    ->default('B')
                                                    ->required(),
                                                TextInput::make('tax_column')
                                                    ->label('Kolom Pajak')
                                                    ->default('C')
                                                    ->required(),
                                            ])
                                    ])
                            ]),

                        // TAB: KEMITRAAN (Loyalty)
                        Tab::make('Kemitraan')
                            ->visible(fn() => app(GeneralSettings::class)->enable_crm)
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Konfigurasi Dasar')
                                    ->description('Pengaturan nama program dan nilai tukar poin.')
                                    ->schema([
                                        TextInput::make('loyalty_program_name')
                                            ->label('Nama Program')
                                            ->default('Sedulur Suralaya')
                                            ->required(),

                                        TextInput::make('loyalty_point_exchange_rate')
                                            ->label('Nilai Belanja per 1 Poin')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(10000)
                                            ->helperText('Contoh: Tiap belanja Rp 10.000 dapat 1 Poin.'),
                                    ]),

                                Section::make('WhatsApp SOP Templates')
                                    ->description('Atur pesan otomatis untuk sapaan WhatsApp.')
                                    ->schema([
                                        Textarea::make('wa_template_phase_1')
                                            ->label('Fase 1: Kunjungan Pertama')
                                            ->rows(4)
                                            ->helperText('Variabel: {name}, {points}'),
                                        Textarea::make('wa_template_phase_2')
                                            ->label('Fase 2: Mulai Repeat (Visit >= 2)')
                                            ->rows(4),
                                        Textarea::make('wa_template_phase_3')
                                            ->label('Fase 3: Naik Tier (Sedulur Tinetes)')
                                            ->rows(4)
                                            ->helperText('Variabel: {name}'),

                                        Section::make('Cheat Sheet FAQ')
                                            ->schema([
                                                Textarea::make('wa_template_faq_benefit')
                                                    ->label('FAQ: Benefit Poin')
                                                    ->rows(3),
                                                Textarea::make('wa_template_faq_redemption')
                                                    ->label('FAQ: Penukaran Hadiah')
                                                    ->rows(3),
                                                Textarea::make('wa_template_faq_use_points')
                                                    ->label('FAQ: Bisa Dipakai Sekarang?')
                                                    ->rows(3),
                                            ]),
                                    ]),
                            ]),

                        // TAB: MODULES
                        Tab::make('Modules (PRO)')
                            ->icon('heroicon-o-sparkles')
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
                                    ]),

                                Section::make('KDS (Kitchen Display System)')
                                    ->description('Manage Kitchen Display System Module.')
                                    ->schema([
                                        TextInput::make('kds_license_key')
                                            ->label('License Key')
                                            ->password() // Hide characters
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: KDS-PRO-XXXX')
                                            ->live(onBlur: true) // Validate on blur
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                // Simple Logic: Key must start with KDS-PRO-
                                                if ($state && str_starts_with($state, 'KDS-PRO-')) {
                                                    // Valid
                                                } else {
                                                    // Invalid: Force disable toggle
                                                    $set('enable_kds', false);
                                                }
                                            }),

                                        Toggle::make('enable_kds')
                                            ->label('Enable KDS Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('kds_license_key') ?? '', 'KDS-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('kds_license_key') ?? '', 'KDS-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul KDS.'
                                            ),
                                    ]),

                                Section::make('Fiscal (Tax & Planning)')
                                    ->description('Manage Fiscal Planning and Tax Reports.')
                                    ->schema([
                                        TextInput::make('fiscal_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: FISCAL-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'FISCAL-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_fiscal_planning', false);
                                                }
                                            }),

                                        Toggle::make('enable_fiscal_planning')
                                            ->label('Aktifkan Modul Perencanaan Fiskal')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('fiscal_license_key') ?? '', 'FISCAL-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('fiscal_license_key') ?? '', 'FISCAL-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan fitur target omzet harian dan randomizer.'
                                            ),
                                    ]),

                                Section::make('CRM (Loyalty & Member)')
                                    ->description('Manage Customer Loyalty, Points, and Tiers.')
                                    ->schema([
                                        TextInput::make('crm_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: CRM-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'CRM-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_crm', false);
                                                }
                                            }),

                                        Toggle::make('enable_crm')
                                            ->label('Enable CRM Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('crm_license_key') ?? '', 'CRM-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('crm_license_key') ?? '', 'CRM-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul Kemitraan.'
                                            ),
                                    ]),

                            ]),
                    ]),
            ]);
    }
}
