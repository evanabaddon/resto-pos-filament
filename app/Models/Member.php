<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'points_balance',
        'total_spend',
        'total_visits',
        'last_visit_at',
        'tier_id',
    ];

    protected $casts = [
        'last_visit_at' => 'datetime',
        'total_spend' => 'decimal:2',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function addPoints(int $amount): void
    {
        $this->increment('points_balance', $amount);
    }

    public function redeemPoints(int $amount): bool
    {
        if ($this->points_balance >= $amount) {
            $this->decrement('points_balance', $amount);
            return true;
        }
        return false;
    }

    public function recordVisit(float $spendAmount): void
    {
        $this->increment('total_visits');
        $this->increment('total_spend', $spendAmount);
        $this->update(['last_visit_at' => now()]);

        $this->checkTierUpgrade();
    }

    public function checkTierUpgrade(): void
    {
        // Find highest tier eligible
        $eligibleTier = LoyaltyTier::where('min_visits', '<=', $this->total_visits)
            ->orderBy('min_visits', 'desc')
            ->orderBy('min_points', 'desc')
            ->first();

        if ($eligibleTier && $eligibleTier->id !== $this->tier_id) {
            $this->update(['tier_id' => $eligibleTier->id]);
        }
    }
}
