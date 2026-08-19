<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderWorkingHour extends Model
{
    protected $fillable = [
        'provider_id',
        'day',
        'is_available',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}