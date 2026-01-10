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

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return __('messages.general_settings');
    }

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
                                        Section::make(__('messages.address_location'))
                                            ->description(__('messages.address_location_desc'))
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('province_code')
                                                            ->label(__('messages.province'))
                                                            ->searchable()
                                                            ->live()
                                                            ->options(function () {
                                                                try {
                                                                    // Fetch provinces
                                                                    /** @var \Illuminate\Http\Client\Response $response */
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
                                                                        /** @var \Illuminate\Http\Client\Response $response */
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
                                                            ->label(__('messages.regency'))
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('province_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('province_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    /** @var \Illuminate\Http\Client\Response $response */
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
                                                                        /** @var \Illuminate\Http\Client\Response $response */
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
                                                            ->label(__('messages.district'))
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('regency_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('regency_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    /** @var \Illuminate\Http\Client\Response $response */
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
                                                                        /** @var \Illuminate\Http\Client\Response $response */
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
                                                            ->label(__('messages.village'))
                                                            ->searchable()
                                                            ->live()
                                                            ->disabled(fn(Get $get) => !$get('district_code'))
                                                            ->options(function (Get $get) {
                                                                $code = $get('district_code');
                                                                if (!$code)
                                                                    return [];

                                                                try {
                                                                    /** @var \Illuminate\Http\Client\Response $response */
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
                                                                        /** @var \Illuminate\Http\Client\Response $response */
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
                                                            ->label(__('messages.postal_code'))
                                                            ->numeric(),

                                                        TextInput::make('bmkg_location_code')
                                                            ->label(__('messages.bmkg_code_auto'))
                                                            ->readOnly()
                                                            ->columnSpan(2)
                                                            ->helperText(__('messages.bmkg_code_helper'))
                                                            ->suffixAction(
                                                                Action::make('test_bmkg')
                                                                    ->icon('heroicon-o-beaker')
                                                                    ->label(__('messages.test'))
                                                                    ->action(function ($state) {
                                                                        if (!$state) {
                                                                            \Filament\Notifications\Notification::make()->title(__('messages.code_not_filled'))->warning()->send();
                                                                            return;
                                                                        }

                                                                        try {
                                                                            /** @var \Illuminate\Http\Client\Response $response */
                                                                            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get("https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$state}");

                                                                            if (!$response) {
                                                                                throw new \Exception(__('messages.failed_fetch_data'));
                                                                            }

                                                                            if ($response->successful()) {
                                                                                $data = $response->json();
                                                                                $lokasi = $data['lokasi'] ?? [];
                                                                                $nama = "{$lokasi['desa']}, {$lokasi['kecamatan']}, {$lokasi['kotkab']}";

                                                                                \Filament\Notifications\Notification::make()
                                                                                    ->title(__('messages.code_valid'))
                                                                                    ->body(__('messages.location_found', ['name' => $nama]))
                                                                                    ->success()
                                                                                    ->send();
                                                                            } else {
                                                                                \Filament\Notifications\Notification::make()->title(__('messages.failed_fetch_data'))->body(__('messages.check_code_server'))->danger()->send();
                                                                            }
                                                                        } catch (\Exception $e) {
                                                                            \Filament\Notifications\Notification::make()->title(__('messages.connection_error'))->body($e->getMessage())->danger()->send();
                                                                        }
                                                                    })
                                                            ),
                                                    ]),
                                            ]),

                                        Section::make(__('messages.app_identity'))
                                            ->columnSpan(1)
                                            ->schema([
                                                TextInput::make('app_name')
                                                    ->label(__('messages.app_name'))
                                                    ->required()
                                                    ->placeholder('Nama Restoran Anda'),

                                                FileUpload::make('app_logo')
                                                    ->label(__('messages.app_logo'))
                                                    ->image()
                                                    ->disk('public') // Ensure public visibility
                                                    ->directory('settings/logo')
                                                    ->maxSize(2048),

                                                FileUpload::make('app_favicon')
                                                    ->label(__('messages.app_favicon'))
                                                    ->image()
                                                    ->disk('public') // Ensure public visibility
                                                    ->directory('settings/favicon')
                                                    ->maxSize(512),
                                            ]),

                                        Section::make(__('messages.company_info'))
                                            ->columnSpan(1)
                                            ->schema([
                                                Textarea::make('company_address')
                                                    ->label(__('messages.company_address'))
                                                    ->rows(3),
                                                TextInput::make('company_phone')
                                                    ->label(__('messages.company_phone')),
                                                TextInput::make('company_email')
                                                    ->label(__('messages.company_email'))
                                                    ->email(),
                                                TextInput::make('app_website')
                                                    ->label(__('messages.website'))
                                                    ->url(),
                                            ]),

                                        Section::make(__('messages.pos_configuration'))
                                            ->columnSpan(1)
                                            ->schema([
                                                Toggle::make('enable_table_number')
                                                    ->label(__('messages.enable_table_number'))
                                                    ->helperText(__('messages.enable_table_number_helper')),

                                                Toggle::make('enable_tax')
                                                    ->label(__('messages.enable_tax'))
                                                    ->live()
                                                    ->helperText(__('messages.enable_tax_helper')),

                                                TextInput::make('tax_percentage')
                                                    ->label(__('messages.tax_percentage'))
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->visible(fn(Get $get) => $get('enable_tax'))
                                                    ->default(0),

                                                Select::make('printer_width')
                                                    ->label(__('messages.printer_width'))
                                                    ->options([
                                                        '58mm' => '58mm (Standard)',
                                                        '80mm' => '80mm (Large)',
                                                    ])
                                                    ->required()
                                                    ->default('58mm'),

                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('operational_start_hour')
                                                            ->label(__('messages.open_hour'))
                                                            ->options(array_combine(range(0, 23), array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23))))
                                                            ->required()
                                                            ->default(10),

                                                        Select::make('operational_end_hour')
                                                            ->label(__('messages.close_hour'))
                                                            ->options(array_combine(range(0, 23), array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23))))
                                                            ->required()
                                                            ->default(22),
                                                    ]),
                                            ]),

                                        Section::make(__('messages.social_media'))
                                            ->columnSpan(2)
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('app_instagram')
                                                            ->label(__('messages.instagram'))
                                                            ->prefix('instagram.com/'),
                                                        TextInput::make('app_tiktok')
                                                            ->label(__('messages.tiktok'))
                                                            ->prefix('tiktok.com/@'),
                                                    ])
                                            ]),

                                        Section::make(__('messages.reservation_config'))
                                            ->columnSpan(2)
                                            ->schema([
                                                Textarea::make('wa_template_reservation_confirmation')
                                                    ->label(__('messages.wa_template_reservation'))
                                                    ->rows(4)
                                                    ->helperText(__('messages.wa_template_reservation_helper')),
                                            ]),
                                    ]),
                            ]),

                        // TAB: FISCAL
                        Tab::make(__('messages.fiscal_tax'))
                            ->visible(fn() => app(GeneralSettings::class)->enable_fiscal_planning)
                            ->icon('heroicon-o-calculator')
                            ->schema([

                                Section::make(__('messages.excel_template_config'))
                                    ->description(__('messages.excel_template_desc'))
                                    ->schema([
                                        FileUpload::make('template_path')
                                            ->label(__('messages.template_file'))
                                            ->disk('public')
                                            ->directory('fiscal-templates')
                                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('start_row')
                                                    ->label(__('messages.start_row'))
                                                    ->numeric()
                                                    ->default(2)
                                                    ->required(),
                                                TextInput::make('date_column')
                                                    ->label(__('messages.date_column'))
                                                    ->default('A')
                                                    ->required(),
                                                TextInput::make('amount_column')
                                                    ->label(__('messages.amount_column'))
                                                    ->default('B')
                                                    ->required(),
                                                TextInput::make('tax_column')
                                                    ->label(__('messages.tax_column'))
                                                    ->default('C')
                                                    ->required(),
                                            ])
                                    ])
                            ]),

                        // TAB: KEMITRAAN (Loyalty)
                        Tab::make(__('messages.partnership'))
                            ->visible(fn() => app(GeneralSettings::class)->enable_crm)
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make(__('messages.basic_config'))
                                    ->description(__('messages.basic_config_desc'))
                                    ->schema([
                                        TextInput::make('loyalty_program_name')
                                            ->label(__('messages.loyalty_program_name'))
                                            ->default('Sedulur Suralaya')
                                            ->required(),

                                        TextInput::make('loyalty_point_exchange_rate')
                                            ->label(__('messages.point_exchange_rate'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(10000)
                                            ->helperText(__('messages.point_exchange_helper')),

                                        TextInput::make('loyalty_point_value')
                                            ->label(__('messages.point_value'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(1)
                                            ->helperText(__('messages.point_value_helper')),
                                    ]),

                                Section::make(__('messages.wa_sop_templates'))
                                    ->description(__('messages.wa_sop_desc'))
                                    ->schema([
                                        Textarea::make('wa_template_phase_1')
                                            ->label(__('messages.phase_1'))
                                            ->rows(4)
                                            ->helperText(__('messages.phase_1_helper')),


                                        Textarea::make('wa_template_phase_2')
                                            ->label(__('messages.phase_2'))
                                            ->rows(4),
                                        Textarea::make('wa_template_phase_3')
                                            ->label(__('messages.phase_3'))
                                            ->rows(4)
                                            ->helperText(__('messages.phase_3_helper')),

                                        Section::make(__('messages.cheat_sheet_faq'))
                                            ->schema([
                                                Textarea::make('wa_template_faq_benefit')
                                                    ->label(__('messages.faq_benefit'))
                                                    ->rows(3),
                                                Textarea::make('wa_template_faq_redemption')
                                                    ->label(__('messages.faq_redemption'))
                                                    ->rows(3),
                                                Textarea::make('wa_template_faq_use_points')
                                                    ->label(__('messages.faq_use_points'))
                                                    ->rows(3),
                                            ]),
                                    ]),
                            ]),

                        // TAB: AI ASSISTANT
                        Tab::make(__('messages.ai_assistant'))
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make(__('messages.ai_intelligence'))
                                    ->description(__('messages.ai_intelligence_desc'))
                                    ->schema([
                                        TextInput::make('ai_assistant_name')
                                            ->label(__('messages.ai_assistant_name'))
                                            ->default('Sarah (AI Admin)')
                                            ->helperText(__('messages.ai_assistant_name_helper'))
                                            ->required(),
                                        Textarea::make('ai_crm_system_prompt')
                                            ->label(__('messages.ai_system_prompt'))
                                            ->rows(6)
                                            ->helperText(__('messages.ai_system_prompt_helper')),

                                        Section::make(__('messages.ai_api_config'))
                                            ->description(__('messages.ai_api_config_desc'))
                                            ->schema([
                                                Select::make('ai_provider')
                                                    ->label(__('messages.ai_provider'))
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
                                                            ->label(__('messages.base_api_url'))
                                                            ->placeholder('https://api.deepseek.com')
                                                            ->required()
                                                            ->helperText(fn(Get $get) => $get('ai_provider') === 'openrouter' ? 'Terisi otomatis untuk OpenRouter.' : 'Endpoint API (OpenAI Compatible).'),

                                                        // Use Dynamic Select for models
                                                        Select::make('ai_model')
                                                            ->label(__('messages.model_name'))
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
                                                                    ->label(__('messages.refresh'))
                                                                    ->tooltip(__('messages.refresh_models'))
                                                                    ->action(function (Get $get) {
                                                                        $provider = $get('ai_provider') ?: 'deepseek';
                                                                        \Illuminate\Support\Facades\Cache::forget("ai_models_{$provider}");
                                                                        \Filament\Notifications\Notification::make()
                                                                            ->title(__('messages.models_refreshed'))
                                                                            ->success()
                                                                            ->send();
                                                                    })
                                                            ),

                                                        TextInput::make('ai_model')
                                                            ->label(__('messages.model_name'))
                                                            ->placeholder('deepseek-chat')
                                                            ->visible(fn(Get $get) => $get('ai_provider') === 'custom')
                                                            ->required(),

                                                        TextInput::make('ai_api_key')
                                                            ->label(__('messages.api_key_optional'))
                                                            ->password()
                                                            ->revealable()
                                                            ->placeholder('sk-...')
                                                            ->helperText(__('messages.api_key_helper')),
                                                    ])
                                            ]),
                                    ]),
                            ]),

                        // TAB: MODULES
                        Tab::make(__('messages.modules_pro'))
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make(__('messages.hrm_module'))
                                    ->description(__('messages.hrm_desc'))
                                    ->schema([
                                        TextInput::make('hrm_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password() // Hide characters
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'HRM-PRO-XXXX']))
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
                                            ->label(__('messages.enable_hrm'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('hrm_license_key') ?? '', 'HRM-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('hrm_license_key') ?? '', 'HRM-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_hrm_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.kds_module'))
                                    ->description(__('messages.kds_desc'))
                                    ->schema([
                                        TextInput::make('kds_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password() // Hide characters
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'KDS-PRO-XXXX']))
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
                                            ->label(__('messages.enable_kds'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('kds_license_key') ?? '', 'KDS-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('kds_license_key') ?? '', 'KDS-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_kds_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.fiscal_module'))
                                    ->description(__('messages.fiscal_desc'))
                                    ->schema([
                                        TextInput::make('fiscal_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'FISCAL-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'FISCAL-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_fiscal_planning', false);
                                                }
                                            }),

                                        Toggle::make('enable_fiscal_planning')
                                            ->label(__('messages.enable_fiscal'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('fiscal_license_key') ?? '', 'FISCAL-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('fiscal_license_key') ?? '', 'FISCAL-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_fiscal_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.crm_module'))
                                    ->description(__('messages.crm_desc'))
                                    ->schema([
                                        TextInput::make('crm_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'CRM-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'CRM-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_crm', false);
                                                }
                                            }),

                                        Toggle::make('enable_crm')
                                            ->label(__('messages.enable_crm'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('crm_license_key') ?? '', 'CRM-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('crm_license_key') ?? '', 'CRM-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_crm_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.wa_center_module'))
                                    ->description(__('messages.wa_center_desc'))
                                    ->schema([
                                        TextInput::make('wa_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'WA-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'WA-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_wa_center', false);
                                                }
                                            }),

                                        Toggle::make('enable_wa_center')
                                            ->label(__('messages.enable_wa_center'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('wa_license_key') ?? '', 'WA-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('wa_license_key') ?? '', 'WA-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_wa_center_desc')
                                            ),

                                        Toggle::make('wa_auto_download_media')
                                            ->label(__('messages.auto_download_media'))
                                            ->helperText(__('messages.auto_download_media_helper'))
                                            ->default(true)
                                            ->visible(fn(Get $get) => $get('enable_wa_center')),
                                    ]),

                                Section::make(__('messages.ai_forecasting_module'))
                                    ->description(__('messages.ai_forecasting_desc'))
                                    ->schema([
                                        TextInput::make('ai_forecasting_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'AI-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'AI-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_ai_forecasting', false);
                                                }
                                            }),

                                        Toggle::make('enable_ai_forecasting')
                                            ->label(__('messages.enable_ai_forecasting'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('ai_forecasting_license_key') ?? '', 'AI-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('ai_forecasting_license_key') ?? '', 'AI-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_ai_forecasting_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.menu_engineering_module'))
                                    ->description(__('messages.menu_engineering_desc'))
                                    ->schema([
                                        TextInput::make('menu_engineering_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'MENU-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'MENU-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_menu_engineering', false);
                                                }
                                            }),

                                        Toggle::make('enable_menu_engineering')
                                            ->label(__('messages.enable_menu_engineering'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('menu_engineering_license_key') ?? '', 'MENU-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('menu_engineering_license_key') ?? '', 'MENU-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_menu_engineering_desc')
                                            ),
                                    ]),

                                Section::make(__('messages.self_order_module'))
                                    ->description(__('messages.self_order_desc'))
                                    ->schema([
                                        TextInput::make('self_order_license_key')
                                            ->label(__('messages.license_key'))
                                            ->password()
                                            ->revealable()
                                            ->revealable()
                                            ->helperText(__('messages.enter_license_key', ['format' => 'ORDER-PRO-XXXX']))
                                            ->live(onBlur: true)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && str_starts_with($state, 'ORDER-PRO-')) {
                                                    // Valid
                                                } else {
                                                    $set('enable_self_order', false);
                                                }
                                            }),

                                        Toggle::make('enable_self_order')
                                            ->label(__('messages.enable_self_order'))
                                            ->inline(false)
                                            ->disabled(
                                                fn(Get $get) =>
                                                !str_starts_with($get('self_order_license_key') ?? '', 'ORDER-PRO-')
                                            )
                                            ->helperText(
                                                fn(Get $get) =>
                                                !str_starts_with($get('self_order_license_key') ?? '', 'ORDER-PRO-')
                                                    ? __('messages.license_invalid')
                                                    : __('messages.enable_self_order_desc')
                                            ),
                                    ]),
                            ]),

                        // TAB: MAINTENANCE
                        Tab::make(__('messages.maintenance'))
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make(__('messages.image_optimization'))
                                    ->description(__('messages.image_optimization_desc'))
                                    ->schema([
                                        Actions::make([
                                            Action::make('optimize_images')
                                                ->label(__('messages.optimize_images_now'))
                                                ->color('warning')
                                                ->icon('heroicon-o-bolt')
                                                ->requiresConfirmation()
                                                ->modalHeading(__('messages.optimize_images_heading'))
                                                ->modalDescription(__('messages.optimize_images_modal_desc'))
                                                ->action(function () {
                                                    $products = \App\Models\Product::whereNotNull('image')->get();
                                                    $count = 0;

                                                    try {
                                                        // Initialize Image Manager (Driver: GD)
                                                        // Check if class exists to avoid crash if library not installed
                                                        if (!class_exists(\Intervention\Image\ImageManager::class)) {
                                                            throw new \Exception(__('messages.intervention_not_installed'));
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
                                                                if ($image->width() > 100) {
                                                                    $image->scale(width: 100);
                                                                    $image->save($path, quality: 40);
                                                                    $count++;
                                                                } elseif ($image->width() <= 100) {
                                                                    // Optional: Just compress if not resized?
                                                                    // Let's just re-save to ensure quality is optimized
                                                                    $image->save($path, quality: 40);
                                                                    $count++;
                                                                }
                                                            } catch (\Exception $e) {
                                                                // Ignore individual errors
                                                                \Illuminate\Support\Facades\Log::error("Failed to optimize image {$product->id}: " . $e->getMessage());
                                                            }
                                                        }

                                                        \Filament\Notifications\Notification::make()
                                                            ->title(__('messages.optimization_complete'))
                                                            ->body(__('messages.processed_images', ['count' => $count]))
                                                            ->success()
                                                            ->send();
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title(__('messages.error'))
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
