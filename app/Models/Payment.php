<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
        'provider_id',
        'payment_reference',
        'gateway_reference',
        'amount',
        'platform_fee',
        'provider_amount',
        'currency',
        'gateway',
        'payment_method',
        'status',
        'paid_at',
        'gateway_response',
        'metadata',
        'authorization_code',
        'gateway_transaction_id',
        
        'paid_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            $payment->payment_reference =
                'PAY-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        });
    }

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


    public function logs()
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}