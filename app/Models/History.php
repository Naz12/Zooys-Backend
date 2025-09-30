<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    //
    public function user()
{
    return $this->belongsTo(User::class);
}

public function tool()
{
    return $this->belongsTo(Tool::class);
}

}