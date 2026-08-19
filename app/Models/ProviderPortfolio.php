<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderPortfolio extends Model
{
    protected $fillable = [
        'provider_id',
        'provider_service_id',
        'title',
        'description',
        'cover_image',
        'completed_at',
        'is_active',
    ];

    protected $casts = [
        'completed_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(
            ProviderService::class,
            'provider_service_id'
        );
    }

    public function images()
    {
        return $this->hasMany(
            ProviderPortfolioImage::class,
            'portfolio_id'
        );
    }
}