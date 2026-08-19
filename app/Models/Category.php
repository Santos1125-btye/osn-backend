<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'status',
    ];

    public function providerServices()
    {
        return $this->hasMany(ProviderService::class);
    }
}
