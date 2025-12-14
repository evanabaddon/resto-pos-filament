<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                Grid::make(2)
                    ->columnSpanFull()
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
            ]);
    }
}
