<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the user that owns the chat session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the chat session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    /**
     * Get the last message in the session.
     */
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'session_id')->latest();
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include sessions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the message count for this session.
     */
    public function getMessageCountAttribute()
    {
        return $this->messages()->count();
    }

    /**
     * Get the last activity timestamp.
     */
    public function getLastActivityAttribute()
    {
        $lastMessage = $this->lastMessage;
        return $lastMessage ? $lastMessage->created_at : $this->created_at;
    }

    /**
     * Generate a name from the first message.
     */
    public function generateNameFromMessage($message)
    {
        // Simple name generation - can be enhanced with AI
        $words = explode(' ', $message);
        $firstWords = array_slice($words, 0, 3);
        $name = implode(' ', $firstWords);
        
        // Truncate if too long
        if (strlen($name) > 50) {
            $name = substr($name, 0, 47) . '...';
        }
        
        return $name ?: 'New Chat Session';
    }
}
