<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the session that owns the message.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    /**
     * Scope a query to only include user messages.
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope a query to only include assistant messages.
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope a query to only include recent messages.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Check if this is a user message.
     */
    public function isUserMessage()
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistantMessage()
    {
        return $this->role === 'assistant';
    }

    /**
     * Get the tokens used for this message.
     */
    public function getTokensUsedAttribute()
    {
        return $this->metadata['tokens_used'] ?? 0;
    }

    /**
     * Get the processing time for this message.
     */
    public function getProcessingTimeAttribute()
    {
        return $this->metadata['processing_time'] ?? null;
    }
}
