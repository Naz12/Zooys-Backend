<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\Modules\AIProcessingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatMessageController extends Controller
{
    private $aiProcessingModule;

    public function __construct(AIProcessingModule $aiProcessingModule)
    {
        $this->aiProcessingModule = $aiProcessingModule;
    }

    /**
     * Send a message to a chat session
     */
    public function store(Request $request, $sessionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:2000',
                'conversation_history' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            // Verify session belongs to user
            $session = ChatSession::forUser($user->id)->find($sessionId);
            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $content = $request->input('content');
            $conversationHistory = $request->input('conversation_history', []);

            // Store user message
            $userMessage = ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => $content,
                'metadata' => [
                    'tokens_used' => 0,
                    'processing_time' => null
                ]
            ]);

            // Generate AI response
            $startTime = microtime(true);
            $aiResponse = $this->generateAIResponse($content, $conversationHistory, $session);
            $processingTime = round((microtime(true) - $startTime) * 1000) / 1000;

            // Store AI response
            $aiMessage = ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse,
                'metadata' => [
                    'tokens_used' => $this->estimateTokens($aiResponse),
                    'processing_time' => $processingTime . 's'
                ]
            ]);

            // Update session name if this is the first message
            if ($session->messages()->count() <= 2) { // User + AI message
                $newName = $session->generateNameFromMessage($content);
                if ($newName !== $session->name) {
                    $session->update(['name' => $newName]);
                }
            }

            return response()->json([
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => $userMessage->role,
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at
                ],
                'ai_message' => [
                    'id' => $aiMessage->id,
                    'role' => $aiMessage->role,
                    'content' => $aiMessage->content,
                    'metadata' => $aiMessage->metadata,
                    'created_at' => $aiMessage->created_at
                ],
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'message_count' => $session->message_count
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat message creation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to send message'
            ], 500);
        }
    }

    /**
     * Get messages for a chat session
     */
    public function index(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            // Verify session belongs to user
            $session = ChatSession::forUser($user->id)->find($sessionId);
            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $messages = ChatMessage::where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->paginate(50);

            return response()->json([
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat messages index error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve messages'
            ], 500);
        }
    }

    /**
     * Get conversation history for a session
     */
    public function history(Request $request, $sessionId)
    {
        try {
            $user = $request->user();
            
            // Verify session belongs to user
            $session = ChatSession::forUser($user->id)->find($sessionId);
            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            $messages = ChatMessage::where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->get();

            $conversation = $messages->map(function($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'metadata' => $message->metadata,
                    'created_at' => $message->created_at
                ];
            });

            return response()->json([
                'session_id' => $sessionId,
                'session_name' => $session->name,
                'conversation' => $conversation,
                'total_messages' => $messages->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Chat history error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve conversation history'
            ], 500);
        }
    }

    /**
     * Generate AI response
     */
    private function generateAIResponse($userMessage, $conversationHistory, $session)
    {
        try {
            // Build conversation context
            $context = $this->buildConversationContext($conversationHistory, $session);
            
            // Generate prompt
            $prompt = $this->buildPrompt($userMessage, $context);
            
            // Get AI response
            $result = $this->aiProcessingModule->answerQuestion($request->input('content'), $context);
            $response = $result['answer'];
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('AI response generation error: ' . $e->getMessage());
            return 'I apologize, but I\'m having trouble processing your request right now. Please try again.';
        }
    }

    /**
     * Build conversation context
     */
    private function buildConversationContext($conversationHistory, $session)
    {
        $context = '';
        
        // Add recent messages from session if no history provided
        if (empty($conversationHistory)) {
            $recentMessages = ChatMessage::where('session_id', $session->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse();
            
            foreach ($recentMessages as $message) {
                $role = $message->role === 'user' ? 'User' : 'Assistant';
                $context .= "{$role}: {$message->content}\n";
            }
        } else {
            // Use provided conversation history
            foreach ($conversationHistory as $message) {
                $role = $message['role'] === 'user' ? 'User' : 'Assistant';
                $context .= "{$role}: {$message['content']}\n";
            }
        }
        
        return $context;
    }

    /**
     * Build prompt for AI
     */
    private function buildPrompt($userMessage, $context)
    {
        $prompt = "You are a helpful AI assistant. ";
        $prompt .= "Please respond to the user's message in a helpful and informative way.\n\n";
        
        if (!empty($context)) {
            $prompt .= "Previous conversation:\n{$context}\n\n";
        }
        
        $prompt .= "User: {$userMessage}\n\n";
        $prompt .= "Assistant:";
        
        return $prompt;
    }

    /**
     * Estimate token count
     */
    private function estimateTokens($text)
    {
        return ceil(strlen($text) / 4); // Rough estimation: 1 token ≈ 4 characters
    }
}
