<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'enabled',
        'created_at',
        'updated_at'
    ];

    public function histories()
    {
        return $this->hasMany(History::class);
    }
}