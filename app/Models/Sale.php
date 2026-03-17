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
        'table_number',
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
        'amount_paid',
        'is_paid',
        'paid_at',
        'is_tax_reported',
        'member_id',
        'points_earned',
    ];


    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
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

    protected static function booted()
    {
        // When a sale is updated (e.g. from Filament backend)
        static::updated(function (Sale $sale) {
            // If payment_method_id was changed, sync related data
            if ($sale->wasChanged('payment_method_id')) {
                $newMethodId = $sale->payment_method_id;
                $newMethod = \App\Models\PaymentMethod::find($newMethodId);
                
                if ($newMethod) {
                    // 1. Sync the payment_method string name on the sale itself
                    // We use DB::table to avoid triggering another 'updated' event loop
                    \Illuminate\Support\Facades\DB::table('sales')
                        ->where('id', $sale->id)
                        ->update(['payment_method' => $newMethod->name]);

                    // 2. Sync SalePayment records
                    $payments = $sale->payments;
                    
                    if ($payments->count() <= 1) {
                        // If 0 or 1 payment, we can safely sync it to the new method
                        $sale->payments()->updateOrCreate(
                            ['sale_id' => $sale->id], // match current sale
                            [
                                'payment_method_id' => $newMethodId,
                                'payment_method_name' => $newMethod->name,
                                'amount' => $sale->final_total,
                            ]
                        );
                    }
                }
            }
        });

        // When a sale is deleted (voided), restore the stock
        static::deleted(function (Sale $sale) {
            // Only process if sale was DRAFT (not paid yet)
            // Because draft sales already deducted stock, but when voided, stock should be restored
            if (!$sale->is_paid && $sale->status === 'draft') {
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    if (!$product) continue;

                    // Create stock movement to restore stock (increase)
                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'type' => 'increase',
                        'reason' => 'void_sale',
                        'notes' => "Void Draft Sale #{$sale->invoice_number} - Stok dikembalikan",
                    ]);
                }
            }
        });
    }
}
