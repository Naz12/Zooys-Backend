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
        'metadata',
        'rag_processed_at',
        'chunk_count',
        'rag_enabled'
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'rag_processed_at' => 'datetime',
        'chunk_count' => 'integer',
        'rag_enabled' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class, 'upload_id');
    }
}
