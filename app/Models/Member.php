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
        'last_contacted_at',
    ];

    protected $casts = [
        'last_visit_at' => 'datetime',
        'last_contacted_at' => 'datetime',
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

    public function recordVisit(float $spendAmount, $visitDate = null): void
    {
        $this->increment('total_visits');
        $this->increment('total_spend', $spendAmount);

        $date = $visitDate ? \Carbon\Carbon::parse($visitDate) : now();

        // Only update if this visit is newer than the recorded last visit, 
        // OR if last_visit_at is null.
        if (is_null($this->last_visit_at) || $date->gt($this->last_visit_at)) {
            $this->update(['last_visit_at' => $date]);
        }
        // Force update if needed? Usually we only want the LATEST visit.
        // But if user claims an OLD transaction, we probably don't want to overwrite a NEWER visit.
        // However, user complaint is about "last visit says 4 hours ago" when it should be transaction time.
        // If they claim an old transaction, and they also visited today, "Last Visit" should technically be today.
        // But if this IS the only visit, it should be the transaction date.
        // Let's stick to "update if newer or null" logic for correctness.
        // Wait, if I am claiming a transaction from yesterday, and I have no other visits today.
        // If my last visit in DB was 1 month ago.
        // Then I claim yesterday's transaction. Last visit becomes Yesterday. Correct.
        // If I claim yesterday's transaction, but I already visited Today (and it's recorded).
        // Then Last Visit should stay Today. Correct.
        // So the logic `if ($date > $this->last_visit_at)` is correct.

        // Simplified based on typical request: Just update it or check correctness.
        // The user issue is "It shows claim time instead of transaction time".
        // Use the passed date.

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
