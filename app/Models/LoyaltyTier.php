<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'min_points',
        'min_visits',
        'benefit_description',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'tier_id');
    }
}
