<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\PayrollFormulaResource\Pages;
use App\Models\PayrollFormula;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayrollFormulaResource extends Resource
{
    protected static ?string $model = PayrollFormula::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?string $cluster = HrmCluster::class;

    // RBAC: super_admin, admin, accountant
    public static function canViewAny(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Accountant;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Accountant;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin || auth()->user()->role === \App\Enums\UserRole::Accountant;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->role === \App\Enums\UserRole::SuperAdmin || auth()->user()->role === \App\Enums\UserRole::Admin;
    }

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Rumus Gaji';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Rumus')
                                    ->required()
                                    ->maxLength(255),

                                Toggle::make('settings.is_advanced')
                                    ->label('Mode Advanced (Manual Script)')
                                    ->live()
                                    ->inline(false)
                                    ->default(false),
                            ]),

                        // SIMPLE MODE SECTION
                        Section::make('Konfigurasi Sederhana')
                            ->description('Atur komponen gaji tanpa koding. Script akan digenerate otomatis.')
                            ->visible(fn(Get $get) => !$get('settings.is_advanced'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('settings.overtime_rate')
                                            ->label('Rate Lembur (Per Jam)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.late_penalty')
                                            ->label('Denda Terlambat (Per Kejadian)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        Select::make('settings.salary_type')
                                            ->label('Tipe Gaji')
                                            ->options([
                                                'monthly' => 'Bulanan Tetap (Full)',
                                                'prorated_30' => 'Bulanan Prorata (Dibagi 30 Hari)',
                                                'daily' => 'Harian Murni (Gaji Pokok = Rate Harian)',
                                            ])
                                            ->default('monthly')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.early_leave_penalty')
                                            ->label('Denda Pulang Cepat (Per Kejadian)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.absent_penalty')
                                            ->label('Denda Tidak Masuk (Per Hari)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->helperText('Jika 0, tidak ada potongan alfa khusus (selain tidak dapat gaji harian jika prorata).')
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),
                                    ]),


                                Repeater::make('settings.deductions')
                                    ->label('Potongan Tambahan (Recurring)')
                                    ->helperText('Potongan ini akan selalu diterapkan setiap generate gaji.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama Potongan')
                                            ->placeholder('Contoh: BPJS, Koperasi')
                                            ->required(),
                                        Select::make('type')
                                            ->label('Tipe Potongan')
                                            ->options([
                                                'fixed' => 'Nominal Tetap (Rp)',
                                                'percentage' => 'Persentase dari Gaji Pokok (%)',
                                            ])
                                            ->default('fixed')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),
                                        TextInput::make('amount')
                                            ->label('Jumlah / Persentase')
                                            ->numeric()
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get))
                                    ->columnSpanFull(),
                            ]),

                        // ADVANCED MODE SECTION
                        Section::make('Script Editor')
                            ->schema([
                                Textarea::make('script')
                                    ->label('Script Rumus (PHP Expression)')
                                    ->helperText('Variables: $base_salary, $attendance_days, $overtime_minutes, $late_count, $early_leave_count. Return array of [total, deductions, overtime_amount].')
                                    ->rows(10)
                                    ->required()
                                    ->columnSpanFull()
                                    ->hidden(fn(Get $get) => !$get('settings.is_advanced'))
                                    ->dehydrated(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([

                DeleteBulkAction::make(),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollFormulas::route('/'),
            'create' => Pages\CreatePayrollFormula::route('/create'),
            'edit' => Pages\EditPayrollFormula::route('/{record}/edit'),
        ];
    }

    public static function calculateScript(array $settings): string
    {
        $overtimeRate = (int) ($settings['overtime_rate'] ?? 0);
        $latePenalty = (int) ($settings['late_penalty'] ?? 0);
        $earlyPenalty = (int) ($settings['early_leave_penalty'] ?? 0);
        $absentPenalty = (int) ($settings['absent_penalty'] ?? 0);
        $deductionsList = $settings['deductions'] ?? [];
        $salaryType = $settings['salary_type'] ?? 'monthly';

        $deductionLogic = '';
        foreach ($deductionsList as $deduction) {
            $name = $deduction['name'] ?? 'Potongan';
            $slug = Str::slug($name, '_');
            $type = $deduction['type'] ?? 'fixed';
            $amount = (float) ($deduction['amount'] ?? 0);

            if ($amount <= 0)
                continue;

            $deductionLogic .= "\n    // $name\n";
            if ($type === 'percentage') {
                $deductionLogic .= "    \$val_$slug = (\$base_salary * ($amount / 100));\n";
            } else {
                $deductionLogic .= "    \$val_$slug = $amount;\n";
            }
            $deductionLogic .= "    \$deductions += \$val_$slug;\n";
            $deductionLogic .= "    \$deduction_details['$name'] = \$val_$slug;\n";
        }

        $baseCalculation = '';
        if ($salaryType === 'daily') {
            $baseCalculation = '$total = $base_salary * $attendance_days;';
        } elseif ($salaryType === 'prorated_30') {
            $baseCalculation = '$daily_rate = $base_salary / 30;' . "\n" . '$total = $daily_rate * $attendance_days;';
        } else {
            // monthly
            $baseCalculation = '$total = $base_salary;';
        }

        return <<<PHP
\$overtime_amount = 0;
\$deductions = 0;
\$deduction_details = [];
$baseCalculation

// 1. Calculate Overtime
if (\$overtime_minutes > 0) {
    // Rate per hour: $overtimeRate
    \$overtime_amount = (\$overtime_minutes / 60) * $overtimeRate;
    \$total += \$overtime_amount;
}

// 2. Calculate Attendance Penalties
if (\$late_count > 0) {
    \$deduction_late = \$late_count * $latePenalty;
    \$deductions += \$deduction_late;
    \$deduction_details['Terlambat'] = \$deduction_late;
}

if (\$early_leave_count > 0) {
    \$deduction_early = \$early_leave_count * $earlyPenalty;
    \$deductions += \$deduction_early;
    \$deduction_details['Pulang Cepat'] = \$deduction_early;
}

if (isset(\$absent_days) && \$absent_days > 0) {
     \$deduction_absent = \$absent_days * $absentPenalty;
     \$deductions += \$deduction_absent;
     \$deduction_details['Alpha'] = \$deduction_absent;
}

// 3. Additional Deductions
$deductionLogic

// Total Calculation
\$total -= \$deductions;

return [
    'total' => \$total,
    'overtime_amount' => \$overtime_amount,
    'deductions' => \$deductions,
    'deduction_details' => \$deduction_details,
];
PHP;
    }

    public static function generateScript(Set $set, Get $get): void
    {
        $settings = [
            'overtime_rate' => $get('settings.overtime_rate'),
            'late_penalty' => $get('settings.late_penalty'),
            'early_leave_penalty' => $get('settings.early_leave_penalty'),
            'absent_penalty' => $get('settings.absent_penalty'),
            'deductions' => $get('settings.deductions'),
            'salary_type' => $get('settings.salary_type'),
        ];

        $script = self::calculateScript($settings);
        $set('script', $script);
    }
}
