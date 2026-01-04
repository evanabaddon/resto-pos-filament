<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvConfig extends Model
{
    protected $fillable = [
        'name',
        'images',
        'music_url',
        'slide_duration',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'slide_duration' => 'integer',
    ];

    /**
     * Scope to get active configuration
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot method to ensure only one active config
     */
    protected static function booted()
    {
        static::saving(function ($config) {
            if ($config->is_active) {
                // Deactivate all other configs
                static::where('id', '!=', $config->id)->update(['is_active' => false]);
            }
        });
    }
}
