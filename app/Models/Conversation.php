<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [

        'booking_id',

        'customer_id',

        'provider_id',

        'status',

        'last_message_at',

        'closed_at',

        'conversation_type',

        'support_user_id',

    ];

    protected $casts = [

        'last_message_at' => 'datetime',

        'closed_at' => 'datetime',

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

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)
            ->latestOfMany();
    }

    public function supportUser()
    {
        return $this->belongsTo(
            User::class,
            'support_user_id'
        );
    }
}