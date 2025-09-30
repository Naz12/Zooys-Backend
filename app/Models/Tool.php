<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    //
    public function histories()
{
    return $this->hasMany(History::class);
}

public function tool()
{
    return $this->belongsTo(Tool::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}


}