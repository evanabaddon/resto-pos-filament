<?php

namespace App\Filament\Clusters\Hrm\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                DateTimePicker::make('clock_in'),
                DateTimePicker::make('clock_out'),
                TextInput::make('status')
                    ->required()
                    ->default('present'),
                TextInput::make('snapshot_path')
                    ->default(null),
            ]);
    }
}
