<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBankAccount extends Model
{
    protected $fillable = [

        'provider_id',

        'bank_code',

        'bank_name',

        'account_name',

        'account_number',

        'is_default',

        'is_verified',

    ];

    protected $casts = [

        'is_default' => 'boolean',

        'is_verified' => 'boolean',

    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}