<?php

namespace App\Filament\Resources\TvConfigs\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TvConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Configuration Name')
                    ->required()
                    ->placeholder('e.g., Pagi, Siang, Malam, Umum')
                    ->helperText('Give this configuration a descriptive name')
                    ->maxLength(255),

                FileUpload::make('images')
                    ->label('TV Display Images')
                    ->directory('tv-display')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(10)
                    ->maxSize(5120) // 5MB per file
                    ->columnSpanFull()
                    ->helperText('Upload multiple images for the TV slideshow. You can drag to reorder.'),

                TextInput::make('music_url')
                    ->label('Music/Video URL')
                    ->url()
                    ->placeholder('https://www.youtube.com/watch?v=KULtaIMaaK0')
                    ->helperText('YouTube or other media URL for background music'),

                TextInput::make('slide_duration')
                    ->label('Slide Duration (ms)')
                    ->numeric()
                    ->default(10000)
                    ->required()
                    ->suffix('ms')
                    ->helperText('Duration each image is displayed (in milliseconds)'),

                Toggle::make('is_active')
                    ->label('Active Configuration')
                    ->helperText('Only one configuration can be active at a time')
                    ->default(false),
            ]);
    }
}
