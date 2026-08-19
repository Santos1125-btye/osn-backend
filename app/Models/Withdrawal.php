<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [

        'provider_id',

        'amount',

        'provider_bank_account_id',

        'reference',

        'status',

        'admin_note',

        'approved_at',

        'paid_at',

    ];

    protected static function booted()
    {
        static::creating(function ($withdrawal) {

            $withdrawal->reference =
                'WD-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(substr(uniqid(), -6));

        });
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(
            ProviderBankAccount::class,
            'provider_bank_account_id'
        );
    }
}