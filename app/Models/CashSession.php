<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'user_id',
        'cash_in_hand',
        'cash_out',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getSessionSummaryAttribute()
    {
        return [
            'cash_in_hand' => $this->cash_in_hand,
            'total_cash_sales' => $this->total_cash_sales,
            'total_non_cash_sales' => $this->total_non_cash_sales,
            'expected_cash' => $this->expected_cash,
            'total_completed_sales' => $this->total_completed_sales,
        ];
    }

    // 🔹 TOTAL penjualan CASH yang COMPLETED
    public function getTotalCashSalesAttribute()
    {
        return $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('final_total');
    }

    // 🔹 TOTAL penjualan NON-CASH yang COMPLETED  
    public function getTotalNonCashSalesAttribute()
    {
        return $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', '!=', 'cash')
            ->sum('final_total');
    }

    // 🔹 TOTAL semua penjualan COMPLETED (apapun payment method)
    public function getTotalCompletedSalesAttribute()
    {
        return $this->sales()
            ->where('status', 'completed')
            ->sum('final_total');
    }

    // 🔹 Uang yang seharusnya ada di laci (kas awal + penjualan cash)
    public function getExpectedCashAttribute()
    {
        return $this->cash_in_hand + $this->total_cash_sales;
    }

    // 🔹 Profit kotor dari penjualan cash
    public function getCashProfitAttribute()
    {
        return $this->total_cash_sales;
    }

    // 🔹 Selisih jika ada hitungan fisik
    public function getCashDifferenceAttribute($physicalCash)
    {
        return $physicalCash - $this->expected_cash;
    }
}