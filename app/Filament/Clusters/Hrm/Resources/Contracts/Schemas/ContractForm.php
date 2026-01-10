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
                Section::make(__('messages.contract_details'))
                    ->schema([
                        Select::make('employee_id')
                            ->label(__('messages.employee_resource'))
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable(),
                        Toggle::make('is_active')
                            ->label(__('messages.active_contract'))
                            ->default(true)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->columnSpanFull(),
                        TextInput::make('nominal')
                            ->label(__('messages.nominal_salary'))
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        DatePicker::make('start_date')
                            ->label(__('messages.start_date'))
                            ->required(),
                        DatePicker::make('end_date')
                            ->label(__('messages.end_date'))
                            ->helperText('Kosongkan jika kontrak permanen'), // TODO: Translate helper text?? Let's stick to core labels first. 'active_contract', 'nominal', 'start_date', 'end_date' added.
                        RichEditor::make('content')
                            ->label(__('messages.contract_content'))
                            ->columnSpanFull()
                            ->required(),
                    ]),
                Section::make(__('messages.signature'))
                    ->schema([
                        ViewField::make('signature_path')
                            ->label(__('messages.digital_signature'))
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
