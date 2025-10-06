<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashcardSet extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'input_type',
        'input_content',
        'difficulty',
        'style',
        'total_cards',
        'source_metadata',
        'is_public'
    ];

    protected $casts = [
        'source_metadata' => 'array',
        'is_public' => 'boolean'
    ];

    /**
     * Get the user that owns the flashcard set
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the flashcards for the set
     */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class)->orderBy('order_index');
    }

    /**
     * Generate a title from input content
     */
    public function generateTitle($input, $inputType)
    {
        $maxLength = 50;
        
        switch ($inputType) {
            case 'youtube':
                return 'YouTube Video Flashcards';
            case 'url':
                return 'Web Page Flashcards';
            case 'file':
                return 'Document Flashcards';
            default:
                $title = trim($input);
                if (strlen($title) > $maxLength) {
                    $title = substr($title, 0, $maxLength) . '...';
                }
                return $title ?: 'Text Flashcards';
        }
    }

    /**
     * Get the set with flashcards
     */
    public function scopeWithFlashcards($query)
    {
        return $query->with('flashcards');
    }

    /**
     * Get public sets
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get sets for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
