<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'date',
        'reference',
        'expense_category_id',
        'description',
        'amount',
        'payment_method_id',
        'recipient',
        'notes',
        'user_id',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->reference)) {
                $expense->reference = static::generateReference();
            }
            if (empty($expense->user_id)) {
                $expense->user_id = auth()->id();
            }
        });
    }

    public static function generateReference(): string
    {
        $prefix = 'EXP';
        $date = now()->format('Ymd');
        
        do {
            $number = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $reference = "{$prefix}{$date}{$number}";
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
