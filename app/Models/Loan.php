<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($loan) {
            if (is_null($loan->remaining_amount)) {
                $loan->remaining_amount = $loan->amount;
            }
        });
    }

    protected $fillable = [
        'employee_id',
        'amount',
        'remaining_amount',
        'installment_amount',
        'reason',
        'status',
        'approved_at',
        'start_month_year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved'])->where('remaining_amount', '>', 0);
    }
}
