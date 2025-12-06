<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Carbon\Carbon;

class Reservation extends Model implements Eventable
{
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

        return CalendarEvent::make($this)
            ->title($this->customer_name . ' (' . $this->party_size . ' orang)')
            ->start(Carbon::parse($this->reservation_date))
            ->end($endTime)
            ->extendedProps([
                'status' => $this->status,
                'phone' => $this->customer_phone,
                'notes' => $this->special_requests,
                'party_size' => $this->party_size,
            ]);
    }
}