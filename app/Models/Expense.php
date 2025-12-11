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
        'payment_method_id', // bisa null
        'recipient',
        'notes',
        'user_id',
        'status',
        'approved_at',
        'approved_by',
        'fund_source',
        'cash_session_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Constants untuk fund source
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

    // Helper untuk mendapatkan ID metode pembayaran cash
    public static function getCashPaymentMethodId(): ?int
    {
        return PaymentMethod::where('code', 'cash')->value('id');
    }

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

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    // Cek apakah expense mempengaruhi cash session
    public function affectsCashSession(): bool
    {
        return $this->fund_source === self::FUND_SOURCE_CASHIER;
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
            
            // Jika sumber dana dari kasir, otomatis set payment method ke CASH
            if ($expense->fund_source === self::FUND_SOURCE_CASHIER && empty($expense->payment_method_id)) {
                $expense->payment_method_id = self::getCashPaymentMethodId();
            }
            
            // Jika sumber dana dari kasir, assign cash session aktif
            if ($expense->affectsCashSession() && empty($expense->cash_session_id)) {
                $activeSession = CashSession::where('user_id', $expense->user_id ?? auth()->id())
                    ->where('status', 'open')
                    ->first();
                $expense->cash_session_id = $activeSession?->id;
            }
        });

        // Setelah expense dibuat/approved, update cash session
        static::created(function ($expense) {
            if ($expense->affectsCashSession() && $expense->status === 'approved' && $expense->cashSession) {
                $expense->cashSession->increment('cash_out', $expense->amount);
            }
        });

        // Handle ketika expense di-update
        static::updated(function ($expense) {
            if ($expense->affectsCashSession() && $expense->cashSession) {
                $originalAmount = $expense->getOriginal('amount');
                $newAmount = $expense->amount;
                
                if ($originalAmount != $newAmount) {
                    $difference = $newAmount - $originalAmount;
                    $expense->cashSession->increment('cash_out', $difference);
                }
            }
        });

        // Handle ketika expense dihapus
        static::deleted(function ($expense) {
            if ($expense->affectsCashSession() && $expense->cashSession) {
                $expense->cashSession->decrement('cash_out', $expense->amount);
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