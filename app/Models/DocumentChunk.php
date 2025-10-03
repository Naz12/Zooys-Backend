<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    protected $fillable = [
        'document_id',
        'page_number',
        'chunk_index',
        'text',
        'embedding'
    ];

    protected $casts = [
        'embedding' => 'array'
    ];

    /**
     * Get the document that owns the chunk.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContentUpload::class, 'document_id');
    }
}
