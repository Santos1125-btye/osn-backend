<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    public const TYPE_BOOKING = 'booking';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REVIEW = 'review';

    public const TYPE_WALLET = 'wallet';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_PROMOTION = 'promotion';
    protected $fillable = [

        'user_id',

        'title',

        'message',

        'type',

        'data',

        'is_read',

        'read_at',

    ];

    protected $casts = [

        'data' => 'array',

        'is_read' => 'boolean',

        'read_at' => 'datetime',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}