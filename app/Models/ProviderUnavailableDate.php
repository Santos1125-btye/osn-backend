<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderUnavailableDate extends Model
{
    protected $fillable = [
        'provider_id',
        'date',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}