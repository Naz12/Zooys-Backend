<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    protected $fillable = [
        'upload_id',
        'chunk_index',
        'content',
        'embedding',
        'page_start',
        'page_end',
        'metadata'
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'page_start' => 'integer',
        'page_end' => 'integer',
        'chunk_index' => 'integer'
    ];

    /**
     * Get the upload that owns the chunk
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(ContentUpload::class, 'upload_id');
    }

    /**
     * Calculate cosine similarity with another embedding
     */
    public function calculateSimilarity(array $otherEmbedding): float
    {
        $embedding1 = $this->embedding;
        $embedding2 = $otherEmbedding;

        if (count($embedding1) !== count($embedding2)) {
            return 0.0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $norm1 += $embedding1[$i] * $embedding1[$i];
            $norm2 += $embedding2[$i] * $embedding2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($norm1 * $norm2);
    }
}
