<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_COMMISSION = 'commission';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    protected $fillable = [
        'payment_id',
        'booking_id',
        'customer_id',
        'provider_id',
        'transaction_reference',
        'type',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'metadata',
        'transaction_date',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transaction) {

            $transaction->transaction_reference =
                'TRX-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(substr(uniqid(), -6));

            $transaction->transaction_date = now();

        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
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
}