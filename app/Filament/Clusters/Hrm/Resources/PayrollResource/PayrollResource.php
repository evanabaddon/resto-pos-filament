<?php

namespace App\Filament\Clusters\Hrm\Resources\PayrollResource;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Filament\Clusters\Hrm\Resources\PayrollResource\Pages;
use App\Models\Employee;
use App\Models\Payroll;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Placeholder;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen SDM';

    protected static ?string $cluster = HrmCluster::class;

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_hrm;
    }

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Penggajian (Payroll)';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pegawai & Periode')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->label('Nama Pegawai')
                            ->disabled(),
                        Forms\Components\TextInput::make('month_year')
                            ->label('Periode')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'paid' => 'Lunas (Paid)',
                            ])
                            ->required(),
                    ]),

                Section::make('Rincian Pendapatan')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('base_salary')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('total_attendance_days')
                            ->label('Total Hadir (Hari)')
                            ->numeric()
                            ->helperText(function ($record) {
                                if (!$record || !$record->details)
                                    return null;
                                $d = $record->details;
                                $parts = [];
                                if (($d['sick_days'] ?? 0) > 0)
                                    $parts[] = "Sakit: {$d['sick_days']} hari";
                                if (($d['permission_days'] ?? 0) > 0)
                                    $parts[] = "Izin: {$d['permission_days']} hari";
                                if (($d['paid_leave_days'] ?? 0) > 0)
                                    $parts[] = "Cuti Tahunan: {$d['paid_leave_days']} hari";

                                return count($parts) > 0 ? implode(', ', $parts) : null;
                            }),
                        TextInput::make('total_overtime_minutes')
                            ->label('Total Lembur (Menit)')
                            ->numeric(),
                        TextInput::make('overtime_amount')
                            ->label('Nominal Lembur')
                            ->numeric()
                            ->prefix('Rp'),
                    ]),

                Section::make('Rincian Potongan & Total')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('deduction_details')
                            ->label('Detail Potongan')
                            ->content(function ($record) {
                                if (!$record || !$record->details)
                                    return '-';

                                $details = $record->details;
                                // Try deduction_details array first
                                $breakdown = $details['deduction_details'] ?? [];

                                if (!empty($breakdown) && is_array($breakdown)) {
                                    $lines = [];
                                    foreach ($breakdown as $name => $amount) {
                                        $formatted = number_format($amount, 0, ',', '.');
                                        $lines[] = "<div class='flex justify-between'><span class='font-medium'>$name:</span> <span>Rp $formatted</span></div>";
                                    }
                                    return new \Illuminate\Support\HtmlString(implode('', $lines));
                                }

                                // Fallback legacy
                                $str = [];
                                if (isset($details['late_count']) && $details['late_count'] > 0) {
                                    $str[] = "Terlambat: {$details['late_count']}x";
                                }
                                if (isset($details['early_leave_count']) && $details['early_leave_count'] > 0) {
                                    $str[] = "Pulang Cepat: {$details['early_leave_count']}x";
                                }

                                return count($str) > 0 ? implode(', ', $str) : 'Tidak ada potongan spesifik';
                            }),
                        TextInput::make('deductions')
                            ->label('Total Potongan')
                            ->numeric()
                            ->prefix('Rp'),

                        // Placeholder::make('separator')->content('-------------------'),

                        TextInput::make('total_payout')
                            ->label('Take Home Pay')
                            ->numeric()
                            ->prefix('Rp')
                            ->extraInputAttributes(['class' => 'text-2xl font-bold text-success-600']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                Tables\Columns\TextColumn::make('month_year')
                    ->label('Periode')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Gaji Pokok')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('overtime_amount')
                    ->label('Lembur')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('deductions')
                    ->label('Potongan')
                    ->money('IDR')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('total_payout')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'paid' => 'success',
                    }),
            ])
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Gaji Sebagai LUNAS')
                    ->modalDescription('Anda yakin ingin menandai gaji ini sebagai LUNAS?')
                    ->modalSubmitActionLabel('Tandai LUNAS')
                    ->visible(fn(Payroll $record) => $record->status !== 'paid')
                    ->action(function (Payroll $record) {
                        $record->update(['status' => 'paid']);

                        // Process Loan Payments if any
                        if (isset($record->details['deduction_details'])) {
                            foreach ($record->details['deduction_details'] as $key => $amount) {
                                // Check if key matches "Cicilan Pinjaman (Ref #ID)"
                                if (preg_match('/Ref #(\d+)/', $key, $matches)) {
                                    $loanId = $matches[1];
                                    $loan = \App\Models\Loan::find($loanId);
                                    if ($loan) {
                                        // Create Payment
                                        \App\Models\LoanPayment::create([
                                            'loan_id' => $loan->id,
                                            'payroll_id' => $record->id,
                                            'amount' => $amount,
                                            'paid_at' => now(),
                                            'note' => "Potong Gaji Periode " . $record->month_year,
                                        ]);

                                        // Update Loan Balance
                                        $loan->remaining_amount -= $amount;
                                        if ($loan->remaining_amount <= 0) {
                                            $loan->remaining_amount = 0;
                                            $loan->status = 'paid';
                                        }
                                        $loan->save();
                                    }
                                }
                            }
                        }

                        Notification::make()->title('Gaji ditandai LUNAS')->success()->send();
                    }),

                Action::make('mark_draft')
                    ->label('Batal Lunas')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Batal Lunas Gaji')
                    ->modalDescription('Anda yakin ingin menandai gaji ini sebagai Draft?')
                    ->modalSubmitActionLabel('Batal Lunas')
                    ->visible(fn(Payroll $record) => $record->status === 'paid')
                    ->action(function (Payroll $record) {

                        // Revert Loan Payments
                        $payments = \App\Models\LoanPayment::where('payroll_id', $record->id)->get();
                        foreach ($payments as $payment) {
                            $loan = $payment->loan;
                            if ($loan) {
                                $loan->remaining_amount += $payment->amount;
                                if ($loan->status === 'paid' && $loan->remaining_amount > 0) {
                                    $loan->status = 'approved';
                                }
                                $loan->save();
                            }
                            $payment->delete();
                        }

                        $record->update(['status' => 'draft']);
                        Notification::make()->title('Status dikembalikan ke Draft')->success()->send();
                    }),

                Action::make('print')
                    ->label('Slip')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('payroll.print', $record))
                    ->openUrlInNewTab()
                    ->color('gray'),
                ViewAction::make(),
                EditAction::make()->visible(fn($record) => $record->status !== 'paid'),
                DeleteAction::make()->visible(fn($record) => $record->status !== 'paid' && in_array(auth()->user()->role, ['super_admin', 'admin'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                    BulkAction::make('mark_paid_bulk')
                        ->label('Tandai Lunas (Terpilih)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai Gaji Sebagai LUNAS')
                        ->modalDescription('Anda yakin ingin menandai gaji ini sebagai LUNAS?')
                        ->modalSubmitActionLabel('Tandai LUNAS')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
                                    $record->update(['status' => 'paid']);
                                }
                            }
                            Notification::make()->title('Data terpilih ditandai LUNAS')->success()->send();
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate Payroll')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ])->default(now()->format('m'))->required(),
                        Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = range(now()->year - 1, now()->year + 1);
                                return array_combine($years, $years);
                            })->default(now()->year)->required(),
                    ])
                    ->action(function (array $data) {
                        $month = $data['month'];
                        $year = $data['year'];
                        $period = "$year-$month";

                        $employees = Employee::whereHas('contracts', function ($q) {
                            $q->where('is_active', true);
                        })->get();

                        $count = 0;
                        foreach ($employees as $employee) {
                            // Check existing
                            $existing = Payroll::where('employee_id', $employee->id)->where('month_year', $period)->first();
                            if ($existing) {
                                if ($existing->status === 'paid') {
                                    continue;
                                }
                                // If draft, delete and re-generate
                                $existing->delete();
                            }

                            $contract = $employee->contracts()
                                ->where('is_active', true)
                                ->latest('start_date')
                                ->first();
                            if (!$contract)
                                continue;

                            $baseSalary = $contract->nominal ?? 0;


                            Log::info("DEBUG: Query Params - Year: {$year}, Month: {$month}");

                            // Check total attendances without filter
                            $allCount = $employee->attendances()->count();
                            Log::info("DEBUG: Total Attendances for User (No Filter): {$allCount}");

                            // Load attendances for the period
                            $attendances = $employee->attendances()
                                ->whereYear('date', $year)
                                ->whereMonth('date', $month)
                                ->get();

                            Log::info("DEBUG: Filtered Attendance Count: " . $attendances->count());
                            Log::info("Base Salary: {$baseSalary}");


                            // Calc Overtime
                            $totalOvertimeMins = $attendances->sum('overtime_minutes');
                            // Calc Deductions (Late)
                            $lateCount = $attendances->where('is_late', true)->count();
                            // Early Leave
                            $earlyLeaveCount = $attendances->where('is_early_leave', true)->count();

                            // Leave Logic
                            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
                            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

                            // Fetch approved leaves overlapping this month
                            $leaves = $employee->leaveRequests()
                                ->where('status', 'approved')
                                ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                                    $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                                        ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                                        ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                                            $sub->where('start_date', '<', $startOfMonth)
                                                ->where('end_date', '>', $endOfMonth);
                                        });
                                })
                                ->get();

                            $sickDays = 0;
                            $permissionDays = 0; // Izin (Unpaid)
                            $paidLeaveDays = 0; // Cuti Tahunan (Paid usually)

                            foreach ($leaves as $leave) {
                                // Calculate days falling in this month
                                $s = Carbon::parse($leave->start_date);
                                $e = Carbon::parse($leave->end_date);

                                // Clamp to month
                                if ($s->lt($startOfMonth))
                                    $s = $startOfMonth->copy();
                                if ($e->gt($endOfMonth))
                                    $e = $endOfMonth->copy();

                                $days = $s->diffInDays($e) + 1;

                                if ($leave->type === 'sakit') {
                                    $sickDays += $days;
                                } elseif ($leave->type === 'izin') {
                                    $permissionDays += $days;
                                } elseif ($leave->type === 'cuti_tahunan') {
                                    $paidLeaveDays += $days;
                                }
                            }

                            // Rules:
                            // Sakit -> Paid (Add to attendance)
                            // Izin -> Unpaid (Do nothing, day is missing from attendance)
                            // Cuti Tahunan -> Paid (Add to attendance)

                            $attendanceDays = $attendances->count() + $sickDays + $paidLeaveDays;

                            // DYNAMIC FORMULA CHECK
                            $deduction_details = [];

                            if ($employee->payroll_formula_id && $employee->payrollFormula) {
                                $script = $employee->payrollFormula->script;
                                $formula = $employee->payrollFormula->name;
                                // Prepare variables
                                // Prepare variables for eval scope
                                $base_salary = $baseSalary;
                                $attendance_days = $attendanceDays;
                                $overtime_minutes = $totalOvertimeMins;
                                $late_count = $lateCount;
                                $early_leave_count = $earlyLeaveCount;
                                $sick_days = $sickDays;
                                $permission_days = $permissionDays;

                                // Reset outputs
                                $overtimeAmount = 0;
                                $deductions = 0;
                                $deduction_details = [];

                                // Safety: Remove dangerous functions
                                if (preg_match('/(exec|system|passthru|shell_exec|phpinfo)/i', $script)) {
                                    $totalPayout = $baseSalary;
                                } else {
                                    try {
                                        // If formula contains 'return', eval it directly
                                        // Otherwise prepend return (for simple expressions)
                                        $result = null;
                                        if (str_contains($script, 'return')) {
                                            $result = eval($script);
                                        } else {
                                            $result = eval("return $script;");
                                        }

                                        if (is_array($result)) {
                                            $totalPayout = $result['total'] ?? 0;
                                            $deductions = $result['deductions'] ?? 0;
                                            $overtimeAmount = $result['overtime_amount'] ?? 0;
                                            $deduction_details = $result['deduction_details'] ?? [];
                                        } else {
                                            $totalPayout = $result;
                                        }
                                    } catch (\Throwable $e) {
                                        Log::error("Formula Error: " . $e->getMessage());
                                        $totalPayout = $baseSalary;
                                    }
                                }
                            } else {
                                // STANDARD LOGIC (Default)
                                // Fixed Rate: Salary / 173
                                $hourlyRate = $baseSalary > 0 ? ($baseSalary / 173) : 0;
                                $overtimeAmount = ($totalOvertimeMins / 60) * $hourlyRate;
                                $deductions = $lateCount * 50000; // 50k penalty per late
                                $totalPayout = $baseSalary + $overtimeAmount - $deductions;
                            }

                            // --- LOAN SECTION ---
                            // Check for active loans
                            $activeLoans = \App\Models\Loan::where('employee_id', $employee->id)
                                ->where('status', 'approved')
                                ->where('remaining_amount', '>', 0)
                                ->where('start_month_year', '<=', $period)
                                ->get();

                            $totalLoanDeduction = 0;
                            foreach ($activeLoans as $loan) {
                                // Potong sebesar installment, tapi tidak melebihi sisa
                                $toDeduct = min($loan->installment_amount, $loan->remaining_amount);
                                $totalLoanDeduction += $toDeduct;
                                $deduction_details["Cicilan Pinjaman (Ref #{$loan->id})"] = $toDeduct;
                            }

                            $deductions += $totalLoanDeduction;
                            $totalPayout -= $totalLoanDeduction;
                            // --------------------

                            Log::info("Total Payout Calculated: {$totalPayout}");

                            Payroll::create([
                                'employee_id' => $employee->id,
                                'month_year' => $period,
                                'base_salary' => $baseSalary,
                                'total_attendance_days' => $attendanceDays,
                                'total_overtime_minutes' => $totalOvertimeMins,
                                'overtime_amount' => $overtimeAmount,
                                'deductions' => $deductions,
                                'total_payout' => $totalPayout,
                                'status' => 'draft',
                                'details' => [
                                    'late_count' => $lateCount,
                                    'early_leave_count' => $earlyLeaveCount,
                                    'sick_days' => $sickDays,
                                    'permission_days' => $permissionDays,
                                    'formula_used' => $formula ?? 'Standard',
                                    'deduction_details' => $deduction_details ?? [],
                                ]
                            ]);
                            $count++;
                        }

                        Notification::make()
                            ->title("Berhasil generate $count slip gaji.")
                            ->success()
                            ->send();
                    })
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Pegawai & Periode')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Nama Pegawai'),
                        TextEntry::make('month_year')
                            ->label('Periode'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'draft' => 'gray',
                                'paid' => 'success',
                            }),
                    ]),

                Section::make('Rincian Pendapatan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('base_salary')
                            ->label('Gaji Pokok')
                            ->money('IDR'),
                        TextEntry::make('total_attendance_days')
                            ->label('Total Hadir (Hari)'),
                        TextEntry::make('total_overtime_minutes')
                            ->label('Total Lembur (Menit)'),
                        TextEntry::make('overtime_amount')
                            ->label('Nominal Lembur')
                            ->money('IDR'),
                    ]),

                Section::make('Rincian Potongan & Total')
                    ->schema([
                        TextEntry::make('deduction_details')
                            ->label('Detail Potongan')
                            ->html()
                            ->state(function ($record) {
                                if (!$record || !$record->details)
                                    return '-';

                                $details = $record->details;
                                $breakdown = $details['deduction_details'] ?? [];

                                if (!empty($breakdown) && is_array($breakdown)) {
                                    $lines = [];
                                    foreach ($breakdown as $name => $amount) {
                                        $formatted = number_format($amount, 0, ',', '.');
                                        $lines[] = "<div class='flex justify-between'><span class='font-medium'>$name:</span> <span>Rp $formatted</span></div>";
                                    }
                                    return new \Illuminate\Support\HtmlString(implode('', $lines));
                                }

                                return 'Tidak ada potongan spesifik';
                            }),
                        TextEntry::make('deductions')
                            ->label('Total Potongan')
                            ->money('IDR')
                            ->color('danger'),
                        TextEntry::make('total_payout')
                            ->label('Take Home Pay')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success')
                            ->size(TextSize::Large),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
