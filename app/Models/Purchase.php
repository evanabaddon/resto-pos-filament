<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number',
        'date',
        'supplier_name',
        'status',
        'total',
        'fund_source',
        'receipt_path',
        'cash_session_id'
    ];

    // Constants untuk fund source (mengikuti Expense)
    const FUND_SOURCE_CASHIER = 'cashier';
    const FUND_SOURCE_PETTY_CASH = 'petty_cash';
    const FUND_SOURCE_TRANSFER = 'transfer';
    const FUND_SOURCE_OTHER = 'other';

    public static function getFundSources(): array
    {
        return [
            self::FUND_SOURCE_CASHIER => 'Kasir',
            self::FUND_SOURCE_PETTY_CASH => 'Petty Cash',
            self::FUND_SOURCE_TRANSFER => 'Transfer',
            self::FUND_SOURCE_OTHER => 'Lainnya',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    // Cek apakah purchase mempengaruhi cash session
    public function affectsCashSession(): bool
    {
        return $this->fund_source === self::FUND_SOURCE_CASHIER;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            // Assign cash session aktif jika sumber dana dari kasir
            if ($purchase->affectsCashSession() && empty($purchase->cash_session_id)) {
                $activeSession = CashSession::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->first();
                $purchase->cash_session_id = $activeSession?->id;
            }
        });
    }
}
