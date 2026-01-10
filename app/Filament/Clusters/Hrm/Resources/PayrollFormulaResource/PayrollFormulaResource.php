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

    protected static string|UnitEnum|null $navigationGroup = null; // Removed hardcoded group

    protected static ?string $cluster = HrmCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.payroll_formula_resource');
    }

    public static function getModelLabel(): string
    {
        return __('messages.payroll_formula_resource');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.payroll_formulas_resource'); // Make sure this key exists or use singular
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.hrm_cluster');
    }

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
                                    ->label(__('messages.formula_name'))
                                    ->required()
                                    ->maxLength(255),

                                Toggle::make('settings.is_advanced')
                                    ->label(__('messages.advanced_mode'))
                                    ->live()
                                    ->inline(false)
                                    ->default(false),
                            ]),

                        // SIMPLE MODE SECTION
                        Section::make(__('messages.simple_config'))
                            ->description('Atur komponen gaji tanpa koding. Script akan digenerate otomatis.') // Consider translating desc
                            ->visible(fn(Get $get) => !$get('settings.is_advanced'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('settings.overtime_rate')
                                            ->label(__('messages.overtime_rate'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.late_penalty')
                                            ->label(__('messages.late_penalty'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        Select::make('settings.salary_type')
                                            ->label(__('messages.salary_type'))
                                            ->options([
                                                'monthly' => (__('messages.monthly_fixed')),
                                                'prorated_30' => (__('messages.monthly_prorated')),
                                                'daily' => (__('messages.daily_pure')),
                                            ])
                                            ->default('monthly')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.early_leave_penalty')
                                            ->label(__('messages.early_leave_penalty'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),

                                        TextInput::make('settings.absent_penalty')
                                            ->label(__('messages.absent_penalty'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->helperText('Jika 0, tidak ada potongan alfa khusus (selain tidak dapat gaji harian jika prorata).')
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),
                                    ]),


                                Repeater::make('settings.deductions')
                                    ->label(__('messages.additional_deductions'))
                                    ->helperText('Potongan ini akan selalu diterapkan setiap generate gaji.') // Translate?
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('messages.deduction_name'))
                                            ->placeholder('Contoh: BPJS, Koperasi') // Translate?
                                            ->required(),
                                        Select::make('type')
                                            ->label(__('messages.deduction_type'))
                                            ->options([
                                                'fixed' => (__('messages.fixed_amount')),
                                                'percentage' => (__('messages.percentage')),
                                            ])
                                            ->default('fixed')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::generateScript($set, $get)),
                                        TextInput::make('amount')
                                            ->label(__('messages.amount_percentage'))
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
                        Section::make(__('messages.script_editor'))
                            ->schema([
                                Textarea::make('script')
                                    ->label(__('messages.script_formula'))
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
                    ->label(__('messages.formula_name'))
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
