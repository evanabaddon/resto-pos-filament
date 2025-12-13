<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollFormula extends Model
{
    protected $fillable = ['name', 'script', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];
}
