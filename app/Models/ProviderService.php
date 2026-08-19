<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderService extends Model
{
    protected $fillable = [
        'provider_id',
        'category_id',

        'sub_category',
        'service_name',

        'description',
        'cover_image',

        'pricing_method',

        'price',
        'min_price',
        'max_price',

        'duration_method',

        'duration',
        'min_duration',
        'max_duration',

        'consultation_type',

        'display_order',

        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function portfolios()
    {
        return $this->hasMany(
            ProviderPortfolio::class,
            'provider_service_id'
        );
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'service_id');
    }
}