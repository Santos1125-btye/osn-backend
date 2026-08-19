<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSocialLink extends Model
{
    protected $fillable = [
        'provider_id',
        'platform',
        'url',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}