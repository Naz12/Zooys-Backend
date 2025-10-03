<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentUpload extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'processing_status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
