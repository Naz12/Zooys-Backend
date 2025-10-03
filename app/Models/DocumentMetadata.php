<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMetadata extends Model
{
    protected $fillable = [
        'document_id',
        'total_pages',
        'total_chunks',
        'processing_status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the document that owns the metadata.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContentUpload::class, 'document_id');
    }
}
