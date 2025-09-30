<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'currency',
        'limit',
        'is_active'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}