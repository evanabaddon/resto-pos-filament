<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrintJob extends Model
{
    use HasFactory;

    // NON-AKTIFKAN TIMESTAMPS OTOMATIS
    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'content',
        'printer',
        'division',
        'sale_id',
        'type',
        'status',
        'attempts',
        'error',
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
