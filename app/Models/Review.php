<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProviderService;

class Review extends Model
{
    protected $fillable = [

        'booking_id',

        'customer_id',

        'provider_id',

        'service_id',

        'rating',

        'comment',

    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(
            ProviderService::class,
            'service_id'
        );
    }
}