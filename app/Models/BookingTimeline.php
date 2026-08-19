<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTimeline extends Model
{
    protected $fillable = [
        'booking_id',
        'status',
        'title',
        'description',
        'created_by',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}