<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
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

    public static function canAccess(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin;
    }

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
                                        Section::make('Alamat & Lokasi')
                                            ->description('Pengaturan alamat lengkap dan wilayah (Integrasi Wilayah.id).')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('province_code')
                                                            ->label('Provinsi')
                                                            ->searchable()
                                                            ->live()
                                                            ->options(function () {
                                                                try {
                                                                    // Fetch provinces
                                                                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://wilayah.id/api/provinces.json');
                                                                    if ($response->successful()) {
                                                                        return collect($response->json('data'))->pluck('name', 'code');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                }
                                                                return [];
                                                            })
                                                            ->afterStateUpdated(function (Set $set, $state) {
                                                                $set('regency_code', null);
                                                                $set('district_code', null);
                                                                $set('village_code', null);

                                                                // Set Name
                                                                if ($state) {
                                                                    try {
                                                                        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://wilayah.id/api/provinces.json');
                                                                        if ($response->successful()) {
                                                                            $name = collect($response->json('data'))->where('code', $state)->first()['name'] ?? null;
                                                                            $set('province_name', $name);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                    }
                                                                }
                                                            }),

                                                        Select::make('regency_code')
                                                            ->label('Kabupaten/Kota')
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('province_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('province_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/regencies/{$code}.json");
                                                                    if ($response->successful()) {
                                                                        return collect($response->json('data'))->pluck('name', 'code');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                }
                                                                return [];
                                                            })
                                                            ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                                                $set('district_code', null);
                                                                $set('village_code', null);

                                                                // Set Name
                                                                if ($state) {
                                                                    try {
                                                                        $pCode = $get('province_code');
                                                                        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/regencies/{$pCode}.json");
                                                                        if ($response->successful()) {
                                                                            $name = collect($response->json('data'))->where('code', $state)->first()['name'] ?? null;
                                                                            $set('regency_name', $name);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                    }
                                                                }
                                                            }),

                                                        Select::make('district_code')
                                                            ->label('Kecamatan')
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('regency_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('regency_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/districts/{$code}.json");
                                                                    if ($response->successful()) {
                                                                        return collect($response->json('data'))->pluck('name', 'code');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                }
                                                                return [];
                                                            })
                                                            ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                                                $set('village_code', null);

                                                                // Set Name
                                                                if ($state) {
                                                                    try {
                                                                        $rCode = $get('regency_code');
                                                                        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/districts/{$rCode}.json");
                                                                        if ($response->successful()) {
                                                                            $name = collect($response->json('data'))->where('code', $state)->first()['name'] ?? null;
                                                                            $set('district_name', $name);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                    }
                                                                }
                                                            }),

                                                        Select::make('village_code')
                                                            ->label('Kelurahan/Desa')
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('district_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('district_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/villages/{$code}.json");
                                                                    if ($response->successful()) {
                                                                        return collect($response->json('data'))->pluck('name', 'code');
                                                                    }
                                                                } catch (\Exception $e) {
                                                                }
                                                                return [];
                                                            })
                                                            ->afterStateUpdated(function (Set $set, $state, Get $get) {
                                                                // Set Name
                                                                if ($state) {
                                                                    try {
                                                                        $dCode = $get('district_code');
                                                                        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://wilayah.id/api/villages/{$dCode}.json");
                                                                        if ($response->successful()) {
                                                                            $name = collect($response->json('data'))->where('code', $state)->first()['name'] ?? null;
                                                                            $set('village_name', $name);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                    }

                                                                    // Auto Set BMKG Code
                                                                    // BMKG usually uses ADM4 code (Village Code)
                                                                    $set('bmkg_location_code', $state);
                                                                }
                                                            }),

                                                        TextInput::make('postal_code')
                                                            ->label('Kode Pos')
                                                            ->numeric(),

                                                        TextInput::make('bmkg_location_code')
                                                            ->label('Kode Lokasi BMKG (Auto)')
                                                            ->readOnly()
                                                            ->columnSpan(2)
                                                            ->helperText('Otomatis terisi berdasarkan Kelurahan/Desa yang dipilih. Digunakan untuk fitur cuaca.')
                                                            ->suffixAction(
                                                                Action::make('test_bmkg')
                                                                    ->icon('heroicon-o-beaker')
                                                                    ->label('Test')
                                                                    ->action(function ($state) {
                                                                        if (!$state) {
                                                                            \Filament\Notifications\Notification::make()->title('Kode belum terisi')->warning()->send();
                                                                            return;
                                                                        }

                                                                        try {
                                                                            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$state}");

                                                                            if (!$response) {
                                                                                throw new \Exception('Gagal menghubungi server BMKG (Empty Response).');
                                                                            }

                                                                            if ($response->successful()) {
                                                                                $data = $response->json();
                                                                                $lokasi = $data['lokasi'] ?? [];
                                                                                $nama = "{$lokasi['desa']}, {$lokasi['kecamatan']}, {$lokasi['kotkab']}";

                                                                                \Filament\Notifications\Notification::make()
                                                                                    ->title('Kode Valid!')
                                                                                    ->body("Lokasi ditemukan: {$nama}")
                                                                                    ->success()
                                                                                    ->send();
                                                                            } else {
                                                                                \Filament\Notifications\Notification::make()->title('Gagal mengambil data.')->body('Pastikan kode benar/server BMKG sedang bermasalah.')->danger()->send();
                                                                            }
                                                                        } catch (\Exception $e) {
                                                                            \Filament\Notifications\Notification::make()->title('Connection Error')->body($e->getMessage())->danger()->send();
                                                                        }
                                                                    })
                                                            ),
                                                    ]),
                                            ]),

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

                                                Toggle::make('enable_tax')
                                                    ->label('Aktifkan Pajak')
                                                    ->live()
                                                    ->helperText('Jika aktif, pajak akan dihitung pada setiap transaksi'),

                                                TextInput::make('tax_percentage')
                                                    ->label('Persentase Pajak (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->visible(fn(Get $get) => $get('enable_tax'))
                                                    ->default(0),

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
                                            ]),

                                        Section::make('Konfigurasi Reservasi')
                                            ->columnSpan(2)
                                            ->schema([
                                                Textarea::make('wa_template_reservation_confirmation')
                                                    ->label('Template Konfirmasi Reservasi Pro (WhatsApp)')
                                                    ->rows(4)
                                                    ->helperText('Variabel: {customer_name}, {app_name}, {date}, {time}, {guests}'),
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

                                        TextInput::make('loyalty_point_value')
                                            ->label('Nilai 1 Poin dalam Rupiah')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(1)
                                            ->helperText('Contoh: 1 Poin bernilai Rp 1 saat ditukarkan.'),
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

                        // TAB: AI ASSISTANT
                        Tab::make('AI Assistant')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('AI Intelligence')
                                    ->description('Atur persona dan instruksi khusus untuk asisten cerdas.')
                                    ->schema([
                                        TextInput::make('ai_assistant_name')
                                            ->label('Nama Asisten AI')
                                            ->default('Sarah (AI Admin)')
                                            ->helperText('Nama ini akan digunakan saat AI membalas chat atau mengirim pesan WA.')
                                            ->required(),
                                        Textarea::make('ai_crm_system_prompt')
                                            ->label('AI Smart Message Prompt (Instruction)')
                                            ->rows(6)
                                            ->helperText('Gunakan instruksi ini untuk mengatur gaya bahasa AI. Variabel tersedia: {app_name}, {program_name}, {available_promos}, {ai_name}'),

                                        Section::make('Konfigurasi API AI')
                                            ->description('Pengaturan teknis koneksi ke penyedia AI (DeepSeek, OpenRouter, OpenAI, dll).')
                                            ->schema([
                                                Select::make('ai_provider')
                                                    ->label('AI Provider')
                                                    ->options([
                                                        'deepseek' => 'DeepSeek (Default)',
                                                        'openrouter' => 'OpenRouter (Free Models & More)',
                                                        'custom' => 'Custom (OpenAI Compatible)',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state === 'deepseek') {
                                                            $set('ai_base_url', 'https://api.deepseek.com');
                                                            $set('ai_model', 'deepseek-chat');
                                                        } elseif ($state === 'openrouter') {
                                                            $set('ai_base_url', 'https://openrouter.ai/api/v1');
                                                            $set('ai_model', 'google/gemini-2.0-flash-exp:free');
                                                        }
                                                    }),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('ai_base_url')
                                                            ->label('Base API URL')
                                                            ->placeholder('https://api.deepseek.com')
                                                            ->required()
                                                            ->helperText(fn(Get $get) => $get('ai_provider') === 'openrouter' ? 'Terisi otomatis untuk OpenRouter.' : 'Endpoint API (OpenAI Compatible).'),

                                                        // Use Dynamic Select for models
                                                        Select::make('ai_model')
                                                            ->label('Model Name')
                                                            ->options(function (Get $get) {
                                                                $provider = $get('ai_provider') ?: 'deepseek';
                                                                if ($provider === 'custom')
                                                                    return [];

                                                                return app(\App\Services\DeepSeekService::class)->getAvailableModels($provider);
                                                            })
                                                            ->visible(fn(Get $get) => $get('ai_provider') !== 'custom')
                                                            ->searchable()
                                                            ->required()
                                                            ->suffixAction(
                                                                Action::make('refresh_models')
                                                                    ->icon('heroicon-m-arrow-path')
                                                                    ->label('Refresh')
                                                                    ->tooltip('Perbarui daftar model dari API')
                                                                    ->action(function (Get $get) {
                                                                        $provider = $get('ai_provider') ?: 'deepseek';
                                                                        \Illuminate\Support\Facades\Cache::forget("ai_models_{$provider}");
                                                                        \Filament\Notifications\Notification::make()
                                                                            ->title('Daftar model diperbarui!')
                                                                            ->success()
                                                                            ->send();
                                                                    })
                                                            ),

                                                        TextInput::make('ai_model')
                                                            ->label('Model Name')
                                                            ->placeholder('deepseek-chat')
                                                            ->visible(fn(Get $get) => $get('ai_provider') === 'custom')
                                                            ->required(),

                                                        TextInput::make('ai_api_key')
                                                            ->label('API Key (Optional)')
                                                            ->password()
                                                            ->revealable()
                                                            ->placeholder('sk-...')
                                                            ->helperText('Jika kosong, akan menggunakan key dari file .env'),
                                                    ])
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

                                Section::make('WhatsApp Center (Official Style)')
                                    ->description('Manage Native WhatsApp Chat Integration.')
                                    ->schema([
                                        TextInput::make('wa_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: WA-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'WA-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_wa_center', false);
                                                }
                                            }),

                                        Toggle::make('enable_wa_center')
                                            ->label('Enable WhatsApp Center')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('wa_license_key') ?? '', 'WA-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('wa_license_key') ?? '', 'WA-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul WhatsApp Center.'
                                            ),

                                        Toggle::make('wa_auto_download_media')
                                            ->label('Auto Download Media')
                                            ->helperText('Jika dinonaktifkan, media hanya didownload saat diklik (menghemat storage).')
                                            ->default(true)
                                            ->visible(fn(Get $get) => $get('enable_wa_center')),
                                    ]),

                                Section::make('AI Forecasting (Smart Restock)')
                                    ->description('AI-powered predictive restocking based on sales trends.')
                                    ->schema([
                                        TextInput::make('ai_forecasting_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: AI-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'AI-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_ai_forecasting', false);
                                                }
                                            }),

                                        Toggle::make('enable_ai_forecasting')
                                            ->label('Enable AI Forecasting Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('ai_forecasting_license_key') ?? '', 'AI-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('ai_forecasting_license_key') ?? '', 'AI-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul prediksi restock cerdas.'
                                            ),
                                    ]),

                                Section::make('AI Menu Engineering (Profit Matrix)')
                                    ->description('AI-powered menu classification and strategic pricing advice.')
                                    ->schema([
                                        TextInput::make('menu_engineering_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: MENU-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'MENU-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_menu_engineering', false);
                                                }
                                            }),

                                        Toggle::make('enable_menu_engineering')
                                            ->label('Enable AI Menu Engineering Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('menu_engineering_license_key') ?? '', 'MENU-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('menu_engineering_license_key') ?? '', 'MENU-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul klasifikasi menu cerdas.'
                                            ),
                                    ]),

                                Section::make('Self Order (QR Menu)')
                                    ->description('Turn your tables into revenue generators with AI-powered Self Order.')
                                    ->schema([
                                        TextInput::make('self_order_license_key')
                                            ->label('License Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Masukkan lisensi, format: ORDER-PRO-XXXX')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'ORDER-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_self_order', false);
                                                }
                                            }),

                                        Toggle::make('enable_self_order')
                                            ->label('Enable Self Order Module')
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('self_order_license_key') ?? '', 'ORDER-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('self_order_license_key') ?? '', 'ORDER-PRO-')
                                                ? 'License key tidak valid. Masukkan key yang benar untuk mengaktifkan.'
                                                : 'Aktifkan modul Self Order QR.'
                                            ),
                                    ]),
                            ]),

                        // TAB: MAINTENANCE
                        Tab::make('Maintenance')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Image Optimization')
                                    ->description('Resize & optimize all product images to reduce size for POS performance.')
                                    ->schema([
                                        Actions::make([
                                            Action::make('optimize_images')
                                                ->label('Optimize All Images Now')
                                                ->color('warning')
                                                ->icon('heroicon-o-bolt')
                                                ->requiresConfirmation()
                                                ->modalHeading('Optimize All Product Images?')
                                                ->modalDescription('This will resize all product images to max 800px width and compress them to 80% quality. original files will be overwritten.')
                                                ->action(function () {
                                                    $products = \App\Models\Product::whereNotNull('image')->get();
                                                    $count = 0;

                                                    try {
                                                        // Initialize Image Manager (Driver: GD)
                                                        // Check if class exists to avoid crash if library not installed
                                                        if (!class_exists(\Intervention\Image\ImageManager::class)) {
                                                            throw new \Exception('Intervention Image library is not installed.');
                                                        }

                                                        $manager = new \Intervention\Image\ImageManager(
                                                            new \Intervention\Image\Drivers\Gd\Driver()
                                                        );

                                                        foreach ($products as $product) {
                                                            try {
                                                                $path = storage_path('app/public/' . $product->image);
                                                                if (!file_exists($path))
                                                                    continue;

                                                                $image = $manager->read($path);

                                                                // Resize to 800 width (auto height) only if wider than 800
                                                                if ($image->width() > 800) {
                                                                    $image->scale(width: 800);
                                                                    $image->save($path, quality: 80);
                                                                    $count++;
                                                                } elseif ($image->width() <= 800) {
                                                                    // Optional: Just compress if not resized?
                                                                    // Let's just re-save to ensure quality is optimized
                                                                    $image->save($path, quality: 80);
                                                                    $count++;
                                                                }

                                                            } catch (\Exception $e) {
                                                                // Ignore individual errors
                                                                \Illuminate\Support\Facades\Log::error("Failed to optimize image {$product->id}: " . $e->getMessage());
                                                            }
                                                        }

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Optimization Complete')
                                                            ->body("Successfully processed {$count} images.")
                                                            ->success()
                                                            ->send();

                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])->fullWidth(),
                                    ]),
                            ]),
                    ])
            ]);
    }
}
