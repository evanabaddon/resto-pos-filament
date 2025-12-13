<?php

namespace App\Filament\Clusters\Hrm\Resources\Contracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Kontrak')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Nama Pegawai')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable(),
                        Toggle::make('is_active')
                            ->label('Kontrak Aktif')
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->columnSpanFull(),
                        TextInput::make('nominal')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->helperText('Kosongkan jika kontrak permanen'),
                        RichEditor::make('content')
                            ->label('Isi Kontrak')
                            ->columnSpanFull()
                            ->required(),
                    ]),
                Section::make('Tanda Tangan')
                    ->schema([
                        ViewField::make('signature_path')
                            ->label('Tanda Tangan Digital')
                            ->view('filament.forms.components.signature-pad')
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state) || !str_starts_with($state, 'data:image')) {
                                    return $state;
                                }

                                $image_parts = explode(";base64,", $state);
                                $image_base64 = base64_decode($image_parts[1]);
                                $fileName = 'signatures/' . Str::random(40) . '.png';

                                Storage::disk('public')->put($fileName, $image_base64);

                                return $fileName;
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
