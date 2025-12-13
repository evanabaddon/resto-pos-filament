<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_name',
        'user_id',
        'order_type',
        'subtotal',
        'tax',
        'discount',
        'final_total',
        'total',
        'payment_method',
        'payment_method_id', // new
        'cash_session_id',
        'reservation_id',
        'status',
        'note',
        'split_from',
        'split_number',
        'split_into',
    ];


    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Relationship ke parent sale (jika ini adalah split)
    public function splitFrom(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'split_from');
    }

    // Relationship ke child splits (jika ini di-split)
    public function splits(): HasMany
    {
        return $this->hasMany(Sale::class, 'split_from');
    }

}
