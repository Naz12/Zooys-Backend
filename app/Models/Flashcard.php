<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flashcard extends Model
{
    protected $fillable = [
        'flashcard_set_id',
        'question',
        'answer',
        'order_index'
    ];

    /**
     * Get the flashcard set that owns the flashcard
     */
    public function flashcardSet(): BelongsTo
    {
        return $this->belongsTo(FlashcardSet::class);
    }
}
