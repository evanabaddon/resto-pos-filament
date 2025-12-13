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
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollFormulaResource extends Resource
{
    protected static ?string $model = PayrollFormula::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Rumus Gaji';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Rumus')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('settings.is_advanced')
                            ->label('Mode Advanced (Manual Script)')
                            ->live()
                            ->default(false),

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
                            ]),

                        // ADVANCED MODE SECTION
                        Section::make('Script Editor')
                            ->visible(fn(Get $get) => $get('settings.is_advanced'))
                            ->schema([
                                Textarea::make('script')
                                    ->label('Script Rumus (PHP Expression)')
                                    ->helperText('Variables: $base_salary, $attendance_days, $overtime_minutes, $late_count, $early_leave_count. Return array of [total, deductions, overtime_amount].')
                                    ->rows(10)
                                    ->required()
                                    ->columnSpanFull(),
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

    public static function generateScript(Set $set, Get $get): void
    {
        $overtimeRate = (int) $get('settings.overtime_rate');
        $latePenalty = (int) $get('settings.late_penalty');
        $earlyPenalty = (int) $get('settings.early_leave_penalty');
        $absentPenalty = (int) $get('settings.absent_penalty');

        $script = <<<PHP
\$overtime_amount = 0;
\$deductions = 0;
\$total = \$base_salary;

// 1. Calculate Overtime
if (\$overtime_minutes > 0) {
    // Rate per hour: $overtimeRate
    \$overtime_amount = (\$overtime_minutes / 60) * $overtimeRate;
    \$total += \$overtime_amount;
}

// 2. Calculate Deductions
if (\$late_count > 0) {
    \$deduction_late = \$late_count * $latePenalty;
    \$deductions += \$deduction_late;
}

if (\$early_leave_count > 0) {
    \$deduction_early = \$early_leave_count * $earlyPenalty;
    \$deductions += \$deduction_early;
}

// Total Calculation
\$total -= \$deductions;

return [
    'total' => \$total,
    'overtime_amount' => \$overtime_amount,
    'deductions' => \$deductions,
];
PHP;

        $set('script', $script);
    }
}
