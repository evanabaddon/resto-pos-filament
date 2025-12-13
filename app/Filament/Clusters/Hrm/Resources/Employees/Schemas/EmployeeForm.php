<?php

namespace App\Filament\Clusters\Hrm\Resources\Employees\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->maxLength(255),
                        TextInput::make('department')
                            ->label('Departemen')
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->required()
                            ->default('active'),
                        Select::make('payroll_formula_id')
                            ->label('Rumus Gaji')
                            ->relationship('payrollFormula', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                Textarea::make('script')->required(),
                            ]),
                        Select::make('shift_id')
                            ->label('Shift Kerja')
                            ->relationship('shift', 'name')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        FileUpload::make('photo_path')
                            ->label('Foto Profil (Avatar)')
                            ->image()
                            ->directory('employees')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Wajah (Untuk Absensi)')
                    ->description('Ambil 3 foto wajah menggunakan kamera. Sistem akan otomatis mendeteksi wajah dan menyimpan data biometrik.')
                    ->schema([
                        ViewField::make('face_photos')
                            ->label('Kamera Wajah')
                            ->view('filament.forms.components.webcam-capture')
                            ->dehydrateStateUsing(function ($state) {
                                if (!is_array($state))
                                    return $state;

                                $paths = [];
                                foreach ($state as $image) {
                                    // If it's already a path (string not starting with data:), keep it
                                    if (!str_starts_with($image, 'data:image')) {
                                        // It might be a clear path or include /storage/
                                        // We store relative paths in DB.
                                        $cleanPath = str_replace('/storage/', '', $image);
                                        $paths[] = $cleanPath;
                                        continue;
                                    }

                                    // Decode and save new image
                                    $image_parts = explode(";base64,", $image);
                                    if (count($image_parts) < 2)
                                        continue; // Invalid
                    
                                    $image_base64 = base64_decode($image_parts[1]);
                                    $fileName = 'employees/faces/' . Str::random(40) . '.png';

                                    Storage::disk('public')->put($fileName, $image_base64);
                                    $paths[] = $fileName;
                                }
                                return $paths;
                            })
                            ->columnSpanFull(),

                        // Hidden field to store the computed Descriptors from JS
                        Hidden::make('face_descriptor')
                            ->dehydrated(true),
                    ]),

                Section::make('Dokumen Pendukung')
                    ->schema([
                        Repeater::make('documents')
                            ->relationship('documents')
                            ->label('Dokumen Pegawai')
                            ->schema([
                                Select::make('type')
                                    ->label('Jenis Dokumen')
                                    ->options([
                                        'ktp' => 'KTP',
                                        'ijazah' => 'Ijazah',
                                        'cv' => 'CV',
                                        'contract' => 'Kontrak Kerja',
                                        'other' => 'Lainnya',
                                    ])
                                    ->required(),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->directory('employee-documents')
                                    ->visibility('public')
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
