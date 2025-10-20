<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
