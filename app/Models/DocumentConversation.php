<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'doc_id',
        'user_id'
    ];

    /**
     * Get the user that owns the conversation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique conversation ID starting with "zooys."
     */
    public static function generateConversationId(): string
    {
        do {
            $randomId = bin2hex(random_bytes(16));
            $conversationId = 'zooys.' . $randomId;
        } while (self::where('conversation_id', $conversationId)->exists());

        return $conversationId;
    }

    /**
     * Find or create a conversation record for a doc_id and user
     */
    public static function findOrCreateForDoc(string $docId, int $userId, ?string $conversationId = null): self
    {
        // If conversation_id is provided, try to find existing conversation with that ID
        if ($conversationId) {
            $conversation = self::where('conversation_id', $conversationId)
                ->where('doc_id', $docId)
                ->where('user_id', $userId)
                ->first();

            if ($conversation) {
                return $conversation;
            }
            
            // Check if conversation_id exists for different doc_id/user_id
            // If it exists, we'll use updateOrCreate to update it, otherwise create new
            $existingConversation = self::where('conversation_id', $conversationId)->first();
            if ($existingConversation) {
                // Conversation ID exists but for different doc/user - update it
                $existingConversation->update([
                    'doc_id' => $docId,
                    'user_id' => $userId
                ]);
                return $existingConversation->fresh();
            }
        }

        // Find existing conversation for this doc_id and user
        $conversation = self::where('doc_id', $docId)
            ->where('user_id', $userId)
            ->first();

        if ($conversation) {
            // If conversation_id was provided but doesn't match, update it
            if ($conversationId && $conversation->conversation_id !== $conversationId) {
                $conversation->update(['conversation_id' => $conversationId]);
                return $conversation->fresh();
            }
            return $conversation;
        }

        // Use updateOrCreate to handle race conditions
        return self::updateOrCreate(
            [
                'conversation_id' => $conversationId ?? self::generateConversationId(),
            ],
            [
                'doc_id' => $docId,
                'user_id' => $userId
            ]
        );
    }
}
