<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use GeneralSettings;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class AppSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';
    
    protected static ?string $navigationLabel = 'General Settings';

    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('app_name')
                    ->label('App Name')
                    ->required()
                    ->placeholder('Nama Restoran Anda'),
                    
                TextInput::make('app_website')
                    ->label('Website')
                    ->url()
                    ->placeholder('https://restoanda.com'),
                    
                TextInput::make('app_instagram')
                    ->label('Instagram')
                    ->url()
                    ->placeholder('username')
                    ->helperText('Masukkan username Instagram tanpa @'),
                    
                TextInput::make('app_tiktok')
                    ->label('TikTok')
                    ->url()
                    ->placeholder('@username')
                    ->helperText('Masukkan username TikTok dengan @'),
                    
                FileUpload::make('app_logo')
                    ->label('Logo')
                    ->image()
                    ->directory('settings/logo')
                    ->maxSize(2048)
                    ->helperText('Ukuran maksimal 2MB'),
                    
                FileUpload::make('app_favicon')
                    ->label('Favicon')
                    ->image()
                    ->directory('settings/favicon')
                    ->maxSize(512)
                    ->helperText('Ukuran maksimal 512KB, format .ico atau .png'),
            ]);
    }
}
