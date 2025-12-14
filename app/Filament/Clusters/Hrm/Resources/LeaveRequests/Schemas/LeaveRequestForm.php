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
                Section::make('Informasi Pengajuan')
                    ->schema([
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->label('Nama Pegawai')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->options([
                                'sakit' => 'Sakit (Sick Leave)',
                                'izin' => 'Izin (Permission)',
                                'cuti_tahunan' => 'Cuti Tahunan (Annual Leave)',
                                'cuti_khusus' => 'Cuti Khusus (Special Leave)',
                            ])
                            ->label('Jenis Izin/Cuti')
                            ->required(),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label('Mulai Tanggal')
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Sampai Tanggal')
                                ->required(),
                        ]),
                        Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label('Lampiran (Surat Dokter/dll)')
                            ->directory('leave-attachments')
                            ->columnSpanFull(),
                    ]),

                Section::make('Persetujuan (Admin Only)')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Menunggu (Pending)',
                                'approved' => 'Disetujui (Approved)',
                                'rejected' => 'Ditolak (Rejected)',
                            ])
                            ->default('pending')
                            ->required(),
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}