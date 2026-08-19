<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Review;
use App\Models\Conversation;
use App\Models\BookingTimeline;
use App\Models\ProviderService;
use App\Models\Dispute;

class Booking extends Model
{
    protected static function booted(): void
    {
        static::creating(function ($booking) {
            $booking->booking_reference = 'OSN-' .
                now()->format('Ymd') . '-' .
                strtoupper(substr(uniqid(), -6));
        });
    }

    protected $fillable = [

        'booking_reference',

        'customer_id',
        'provider_id',
        'service_id',

        'address_id',

        'service_delivery',

        'booking_date',
        'booking_time',

        'amount',
        'discount_amount',
        'home_service_fee',
        'total_amount',

        'promo_code',

        'estimated_duration',

        'payment_status',
        'status',

        'notes',

        'accepted_at',
        'rejected_at',
        'started_at',
        'provider_completed_at',
        'completed_at',
        'cancelled_at',

    ];

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

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function timelines()
    {
        return $this->hasMany(BookingTimeline::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function dispute()
    {
        return $this->hasOne(Dispute::class);
    }
}