<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportReport extends Model
{
    protected $fillable = [
        'user_id',
        'description',
        'status',
        'admin_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}