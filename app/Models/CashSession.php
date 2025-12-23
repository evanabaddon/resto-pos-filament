<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // 🔹 Relasi ke expenses dari cash session ini
    public function cashExpenses(): HasMany
    {
        return $this->hasMany(Expense::class)->where('fund_source', Expense::FUND_SOURCE_CASHIER);
    }

    // 🔹 Relasi ke purchases dari cash session ini
    public function cashPurchases(): HasMany
    {
        return $this->hasMany(Purchase::class)->where('fund_source', Purchase::FUND_SOURCE_CASHIER)->where('status', 'received');
    }

    public function getSessionSummaryAttribute()
    {
        return [
            'cash_in_hand' => $this->cash_in_hand,
            'total_cash_sales' => $this->total_cash_sales,
            'total_cash_expenses' => $this->total_cash_expenses,
            'total_cash_purchases' => $this->total_cash_purchases,
            'expected_cash' => $this->expected_cash,
            'total_completed_sales' => $this->total_completed_sales,
            'total_transactions' => $this->transaction_count,
        ];
    }

    // 🔹 TOTAL pengeluaran CASH dari session ini
    public function getTotalCashExpensesAttribute(): float
    {
        return $this->cashExpenses()->sum('amount');
    }

    // 🔹 TOTAL pembelian CASH dari session ini
    public function getTotalCashPurchasesAttribute(): float
    {
        return $this->cashPurchases()->sum('total');
    }

    // 🔹 TOTAL penjualan CASH yang COMPLETED
    public function getTotalCashSalesAttribute(): float
    {
        $cashId = PaymentMethod::where('code', 'cash')->value('id');
        return $this->sales()
            ->where('status', 'completed')
            ->where('payment_method_id', $cashId)
            ->sum('final_total');
    }

    // 🔹 TOTAL penjualan NON-CASH yang COMPLETED  
    public function getTotalNonCashSalesAttribute(): float
    {
        return $this->sales()
            ->where('status', 'completed')
            ->where('payment_method_id', '!=', 1)
            ->sum('final_total');
    }

    // 🔹 TOTAL semua penjualan COMPLETED (apapun payment method)
    public function getTotalCompletedSalesAttribute()
    {
        return $this->sales()
            ->where('status', 'completed')
            ->sum('final_total');
    }

    // 🔹 Uang yang seharusnya ada di laci (kas awal + penjualan cash - pengeluaran cash - pembelian cash)
    public function getExpectedCashAttribute(): float
    {
        return $this->cash_in_hand + $this->total_cash_sales - $this->total_cash_expenses - $this->total_cash_purchases;
    }

    // 🔹 Selisih kas aktual dengan kas seharusnya
    public function getCashDifferenceAttribute(): ?float
    {
        if (is_null($this->cash_out)) {
            return null; // belum ditutup
        }

        return $this->cash_out - $this->expected_cash;
    }

    // Jumlah transaksi completed
    public function getTransactionCountAttribute(): int
    {
        return $this->sales()->where('status', 'completed')->count();
    }

    // Rata-rata transaksi completed
    public function getAverageTransactionAttribute(): float
    {
        $count = $this->transaction_count;
        return $count > 0 ? $this->total_completed_sales / $count : 0;
    }
}