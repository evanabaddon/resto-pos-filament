<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Carbon\Carbon;

class Reservation extends Model implements Eventable
{
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class)->where('status', '!=', 'cancelled'); // Assuming deposits are active sales
    }
    protected $fillable = [
        'customer_name',
        'customer_phone', 
        'party_size',
        'reservation_date',
        'status',
        'special_requests',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
    ];

    public function toCalendarEvent(): CalendarEvent
    {
        // Tambah 2 jam untuk end time
        $endTime = Carbon::parse($this->reservation_date)->addHours(2);

        // Definisikan warna berdasarkan status
        $styles = match ($this->status) {
            'pending' => [
                'color: #92400e' => true,
                'background-color: #fef3c7' => true,
                'border-color: #f59e0b' => true,
            ],
            'confirmed' => [
                'color: #065f46' => true,
                'background-color: #d1fae5' => true,
                'border-color: #10b981' => true,
            ],
            'seated' => [
                'color: #1e40af' => true,
                'background-color: #dbeafe' => true,
                'border-color: #3b82f6' => true,
            ],
            'completed' => [
                'color: #374151' => true,
                'background-color: #f3f4f6' => true,
                'border-color: #9ca3af' => true,
            ],
            'cancelled' => [
                'color: #991b1b' => true,
                'background-color: #fee2e2' => true,
                'border-color: #ef4444' => true,
            ],
            default => [
                'color: #374151' => true,
                'background-color: #f3f4f6' => true,
                'border-color: #9ca3af' => true,
            ],
        };
        
        // Tambahkan styles umum
        $styles['border-width: 2px'] = true;
        $styles['border-style: solid'] = true;
        $styles['font-weight: 500'] = true;
        $styles['padding: 4px 8px'] = true;
        $styles['border-radius: 10px'] = true;

        return CalendarEvent::make($this)
            ->title($this->customer_name . ' (' . $this->party_size . ' orang)')
            ->start(Carbon::parse($this->reservation_date))
            ->end($endTime)
            ->action('edit')
            ->extendedProps([
                'status' => $this->status,
                'phone' => $this->customer_phone,
                'notes' => $this->special_requests,
                'party_size' => $this->party_size,
            ])->styles($styles);
    }
}