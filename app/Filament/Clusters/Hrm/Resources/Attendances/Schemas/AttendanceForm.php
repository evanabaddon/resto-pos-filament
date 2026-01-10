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
                    ->label(__('messages.employee_resource'))
                    ->relationship('employee', 'name')
                    ->required(),
                DatePicker::make('date')
                    ->label(__('messages.date'))
                    ->required(),
                DateTimePicker::make('clock_in')
                    ->label(__('messages.clock_in')),
                DateTimePicker::make('clock_out')
                    ->label(__('messages.clock_out')),
                TextInput::make('status')
                    ->label(__('messages.status'))
                    ->required()
                    ->default('present'),
                TextInput::make('snapshot_path')
                    ->default(null),
            ]);
    }
}
