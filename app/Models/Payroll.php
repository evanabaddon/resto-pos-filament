<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month_year',
        'base_salary',
        'total_attendance_days',
        'total_overtime_minutes',
        'overtime_amount',
        'deductions',
        'total_payout',
        'status',
        'details',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'total_payout' => 'decimal:2',
        'details' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }
}
