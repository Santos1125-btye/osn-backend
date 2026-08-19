<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'phone',
        'business_email',
        'bio',
        'business_description',
        'profile_image',
        'cover_image',
        'certificate_file',
        'verification_status',
        'rejection_reason',
        'years_of_experience',

        'country_id',
        'state_id',
        'city_id',
        'onboarding_completed',

        'business_address',
        'latitude',
        'longitude',
        'rating',
        'is_available',
        'is_active',
    ];
    protected $casts = [
        'onboarding_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialLinks()
    {
        return $this->hasMany(ProviderSocialLink::class);
    }

    public function services()
    {
        return $this->hasMany(ProviderService::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(
            ProviderBankAccount::class
        );
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function portfolios()
    {
        return $this->hasMany(ProviderPortfolio::class);
    }

    public function workingHours()
    {
        return $this->hasMany(ProviderWorkingHour::class);
    }

    public function unavailableDates()
    {
        return $this->hasMany(ProviderUnavailableDate::class);
    }

}