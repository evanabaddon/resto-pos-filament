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
                Section::make(__('messages.personal_info') ?? 'Personal Information') // Adding personal_info key later if missing. I'll use literal for now and update dictionary in next batch if needed. Or just use a generic 'Details'. Let's check keys. I didn't add 'personal_info'. I will add it or use 'Details'. I will add 'personal_info' to dictionary next.
                    ->label(__('messages.personal_info') ?? 'Personal Information')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('position')
                            ->label(__('messages.position'))
                            ->maxLength(255),
                        TextInput::make('department')
                            ->label(__('messages.department'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->required()
                            ->default('active'),
                        Select::make('payroll_formula_id')
                            ->label(__('messages.payroll_formula_resource'))
                            ->relationship('payrollFormula', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                Textarea::make('script')->required(),
                            ]),
                        Select::make('shift_id')
                            ->label(__('messages.shift_resource'))
                            ->relationship('shift', 'name')
                            ->required(),
                        TextInput::make('phone')
                            ->label(__('messages.phone'))
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('messages.employee_address'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        FileUpload::make('photo_path')
                            ->label(__('messages.photo_profile'))
                            ->image()
                            ->directory('employees')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('messages.face_data'))
                    ->description(__('messages.face_data_desc'))
                    ->schema([
                        ViewField::make('face_photos')
                            ->label(__('messages.face_camera'))
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

                Section::make(__('messages.supporting_documents'))
                    ->schema([
                        Repeater::make('documents')
                            ->relationship('documents')
                            ->label(__('messages.documents'))
                            ->schema([
                                Select::make('type')
                                    ->label(__('messages.document_type'))
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
