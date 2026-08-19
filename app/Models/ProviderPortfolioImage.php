<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderPortfolioImage extends Model
{
    protected $fillable = [
        'portfolio_id',
        'image',
        'display_order',
    ];

    public function portfolio()
    {
        return $this->belongsTo(
            ProviderPortfolio::class,
            'portfolio_id'
        );
    }
}