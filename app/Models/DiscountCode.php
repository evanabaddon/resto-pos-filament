<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function isValid(): bool
    {
        $today = Carbon::today();

        if (! $this->is_active) return false;
        if ($this->valid_from && $today->lt($this->valid_from)) return false;
        if ($this->valid_until && $today->gt($this->valid_until)) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;

        return true;
    }

    public function applyDiscount(float $total): float
    {
        if (! $this->isValid()) return 0;

        if ($this->min_purchase && $total < $this->min_purchase) {
            return 0;
        }

        $discount = $this->type === 'percentage'
            ? $total * ($this->value / 100)
            : $this->value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 2);
    }
}
