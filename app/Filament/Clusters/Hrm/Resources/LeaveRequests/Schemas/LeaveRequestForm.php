<?php

namespace App\Filament\Clusters\Hrm\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.submission_info'))
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->label(__('messages.employee_resource'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->options([
                                'sakit' => __('messages.sick_leave'),
                                'izin' => __('messages.permission'),
                                'cuti_tahunan' => __('messages.annual_leave'),
                                'cuti_khusus' => __('messages.special_leave'),
                            ])
                            ->label(__('messages.leave_type'))
                            ->required(),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label(__('messages.start_date'))
                                ->required(),
                            DatePicker::make('end_date')
                                ->label(__('messages.end_date'))
                                ->required(),
                        ]),
                        Textarea::make('reason')
                            ->label(__('messages.reason'))
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label(__('messages.attachment'))
                            ->directory('leave-attachments')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.approval_section'))
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => __('messages.pending'),
                                'approved' => __('messages.approved'),
                                'rejected' => __('messages.rejected'),
                            ])
                            ->default('pending')
                            ->required(),
                        Textarea::make('rejection_reason')
                            ->label(__('messages.rejection_reason'))
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}