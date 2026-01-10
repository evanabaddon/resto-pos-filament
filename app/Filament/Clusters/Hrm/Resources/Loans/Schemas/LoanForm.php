<?php

namespace App\Filament\Clusters\Hrm\Resources\Loans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.loan_info'))
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label(__('messages.employee_resource')),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label(__('messages.loan_amount')),
                        TextInput::make('installment_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label(__('messages.installment_amount')),
                        TextInput::make('start_month_year')
                            ->required()
                            ->placeholder('YYYY-MM')
                            ->regex('/^\d{4}-\d{2}$/')
                            ->helperText('Format: YYYY-MM (Contoh: 2024-01)')
                            ->label(__('messages.start_deduction_date')),
                        Textarea::make('reason')
                            ->rows(3)
                            ->label(__('messages.reason')),
                        Select::make('status')
                            ->options([
                                'pending' => __('messages.pending'),
                                'approved' => __('messages.approved'),
                                'rejected' => __('messages.rejected'),
                                'paid' => __('messages.paid'),
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2)
                    ->columnSpan('full'),
            ]);
    }
}
