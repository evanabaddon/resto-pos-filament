<?php

namespace App\Filament\Pages;

use App\Settings\LandingPageSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use BackedEnum;


class ManageLandingPage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string $settings = LandingPageSettings::class;

    protected static ?string $navigationLabel = 'Landing Page';

    public function getTitle(): string
    {
        return __('messages.manage_landing_page');
    }

    protected static ?int $navigationSort = 100;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.hero_section'))
                    ->description(__('messages.hero_section_desc'))
                    ->schema([
                        TextInput::make('hero_title')
                            ->label(__('messages.hero_title'))
                            ->required(),
                        Textarea::make('hero_description')
                            ->label(__('messages.hero_description'))
                            ->rows(3)
                            ->required(),
                        FileUpload::make('hero_image')
                            ->label(__('messages.hero_image'))
                            ->image()
                            ->disk('public')
                            ->directory('landing-page')
                            ->visibility('public')
                            ->imagePreviewHeight('250')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->downloadable()
                            ->fetchFileInformation(false),
                    ]),

                Section::make(__('messages.theme_section'))
                    ->description(__('messages.theme_section_desc'))
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label(__('messages.primary_color'))
                            ->required(),
                        ColorPicker::make('secondary_color')
                            ->label(__('messages.secondary_color'))
                            ->required(),
                    ]),

                Section::make(__('messages.content_section'))
                    ->schema([
                        TextInput::make('about_us_title')
                            ->label(__('messages.about_us_title'))
                            ->default('Authentic Tastes, Modern Twist.')
                            ->required(),
                        Textarea::make('about_us_text')
                            ->label(__('messages.about_us_text'))
                            ->rows(5)
                            ->required(),
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('about_image_1')
                                    ->image()
                                    ->disk('public')
                                    ->directory('landing-page')
                                    ->visibility('public')
                                    ->imagePreviewHeight('200')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                    ->downloadable()
                                    ->fetchFileInformation(false)
                                    ->label(__('messages.about_image_1')),
                                FileUpload::make('about_image_2')
                                    ->image()
                                    ->disk('public')
                                    ->directory('landing-page')
                                    ->visibility('public')
                                    ->imagePreviewHeight('200')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                    ->downloadable()
                                    ->fetchFileInformation(false)
                                    ->label(__('messages.about_image_2')),
                                FileUpload::make('about_image_3')
                                    ->image()
                                    ->disk('public')
                                    ->directory('landing-page')
                                    ->visibility('public')
                                    ->imagePreviewHeight('200')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                    ->downloadable()
                                    ->fetchFileInformation(false)
                                    ->label(__('messages.about_image_3')),
                                FileUpload::make('about_image_4')
                                    ->image()
                                    ->disk('public')
                                    ->directory('landing-page')
                                    ->visibility('public')
                                    ->imagePreviewHeight('200')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                    ->downloadable()
                                    ->fetchFileInformation(false)
                                    ->label(__('messages.about_image_4')),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('stats_years')
                                    ->label(__('messages.stats_years'))
                                    ->default('15+'),
                                TextInput::make('stats_customers')
                                    ->label(__('messages.stats_customers'))
                                    ->default('10k+'),
                            ]),
                    ]),

                Section::make(__('messages.contact_section'))
                    ->schema([
                        FileUpload::make('contact_image')
                            ->label(__('messages.contact_image'))
                            ->image()
                            ->disk('public')
                            ->directory('landing-page')
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->downloadable()
                            ->fetchFileInformation(false),
                    ]),

                Section::make(__('messages.seo_section'))
                    ->description(__('messages.seo_section_desc'))
                    ->schema([
                        TextInput::make('seo_title')
                            ->label(__('messages.seo_title'))
                            ->placeholder('Best Restaurant in Town'),
                        Textarea::make('seo_description')
                            ->label(__('messages.seo_description'))
                            ->rows(2),
                        TextInput::make('seo_keywords')
                            ->label(__('messages.seo_keywords'))
                            ->placeholder('food, dining, restaurant'),
                    ]),
            ]);
    }
}
