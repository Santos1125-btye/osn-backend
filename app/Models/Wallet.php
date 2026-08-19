<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'provider_id',
        'available_balance',
        'pending_balance',
        'total_earned',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}