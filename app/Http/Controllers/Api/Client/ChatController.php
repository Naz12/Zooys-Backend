<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\Tool;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\Modules\AIProcessingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $aiProcessingModule;

    public function __construct(AIProcessingModule $aiProcessingModule)
    {
        $this->aiProcessingModule = $aiProcessingModule;
    }

    /**
     * Chat with AI
     */
    public function chat(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:4000',
                'session_id' => 'nullable|exists:chat_sessions,id',
                'conversation_history' => 'array',
                'conversation_history.*.role' => 'required|string|in:user,assistant',
                'conversation_history.*.content' => 'required|string',
                'model' => 'string|in:gpt-3.5-turbo,gpt-4',
                'temperature' => 'numeric|between:0,2',
                'max_tokens' => 'integer|between:1,4000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $message = $request->input('message');
            $sessionId = $request->input('session_id');
            $conversationHistory = $request->input('conversation_history', []);
            $model = $request->input('model', 'gpt-3.5-turbo');
            $temperature = $request->input('temperature', 0.7);
            $maxTokens = $request->input('max_tokens', 1000);

            // Handle session-based chat
            if ($sessionId) {
                return $this->handleSessionChat($user, $sessionId, $message, $conversationHistory, $model, $temperature, $maxTokens);
            }

            // Get or create AI Chat tool
            $tool = Tool::firstOrCreate(
                ['slug' => 'ai-chat'],
                [
                    'name' => 'AI Chat',
                    'enabled' => true
                ]
            );

            // Build conversation messages
            $messages = $this->buildConversationMessages($message, $conversationHistory);

            // Generate AI response
            $response = $this->generateAIResponse($messages, $model, $temperature, $maxTokens);

            if (!$response) {
                return response()->json([
                    'error' => 'Unable to generate response at this time'
                ], 500);
            }

            // Store in history
            $this->storeChatHistory($user, $tool, $message, $response);

            return response()->json([
                'response' => $response,
                'model_used' => $model,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process chat request at this time'
            ], 500);
        }
    }

    /**
     * Build conversation messages for OpenAI
     */
    private function buildConversationMessages($currentMessage, $conversationHistory)
    {
        $messages = [];

        // Add system message
        $messages[] = [
            'role' => 'system',
            'content' => 'You are a helpful AI assistant. Provide clear, accurate, and helpful responses to user questions. Be conversational and engaging while maintaining professionalism.'
        ];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $currentMessage
        ];

        return $messages;
    }

    /**
     * Generate AI response using AI Manager
     */
    private function generateAIResponse($messages, $model, $temperature, $maxTokens)
    {
        try {
            // Extract the current message (last message in array)
            $currentMessage = end($messages)['content'];
            
            // Build context from conversation history (excluding system message and current message)
            $context = '';
            foreach (array_slice($messages, 1, -1) as $msg) {
                if ($msg['role'] !== 'system') {
                    $context .= $msg['role'] . ': ' . $msg['content'] . "\n";
                }
            }
            
            // Use AI Manager for Q&A with context
            $result = $this->aiProcessingModule->answerQuestion($currentMessage, $context ?: null);
            
            return $result['answer'];
            
        } catch (\Exception $e) {
            Log::error('AI Manager API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store chat history
     */
    private function storeChatHistory($user, $tool, $message, $response)
    {
        try {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input' => $message,
                'output' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store chat history: ' . $e->getMessage());
        }
    }

    /**
     * Handle session-based chat
     */
    private function handleSessionChat($user, $sessionId, $message, $conversationHistory, $model, $temperature, $maxTokens)
    {
        try {
            // Verify session belongs to user
            $session = ChatSession::forUser($user->id)->find($sessionId);
            if (!$session) {
                return response()->json([
                    'error' => 'Chat session not found'
                ], 404);
            }

            // Store user message
            $userMessage = ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => $message,
                'metadata' => [
                    'tokens_used' => 0,
                    'processing_time' => null
                ]
            ]);

            // Build conversation messages
            $messages = $this->buildConversationMessages($message, $conversationHistory);

            // Generate AI response
            $startTime = microtime(true);
            $aiResponse = $this->generateAIResponse($messages, $model, $temperature, $maxTokens);
            $processingTime = round((microtime(true) - $startTime) * 1000) / 1000;

            if (!$aiResponse) {
                return response()->json([
                    'error' => 'Unable to generate response at this time'
                ], 500);
            }

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
                $newName = $session->generateNameFromMessage($message);
                if ($newName !== $session->name) {
                    $session->update(['name' => $newName]);
                }
            }

            return response()->json([
                'response' => $aiResponse,
                'session_id' => $sessionId,
                'model_used' => $model,
                'timestamp' => now()->toISOString(),
                'metadata' => [
                    'tokens_used' => $aiMessage->tokens_used,
                    'processing_time' => $aiMessage->processing_time
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Session chat error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process session chat'
            ], 500);
        }
    }

    /**
     * Create new chat session and send first message
     */
    public function createAndChat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:4000',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $message = $request->input('message');
            $name = $request->input('name');
            $description = $request->input('description');

            // Create new session
            $session = ChatSession::create([
                'user_id' => $user->id,
                'name' => $name ?: 'New Chat Session',
                'description' => $description,
                'is_active' => true
            ]);

            // Send message to new session
            return $this->handleSessionChat($user, $session->id, $message, [], 'gpt-3.5-turbo', 0.7, 1000);

        } catch (\Exception $e) {
            Log::error('Create and chat error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to create session and chat'
            ], 500);
        }
    }

    /**
     * Estimate token count
     */
    private function estimateTokens($text)
    {
        return ceil(strlen($text) / 4); // Rough estimation: 1 token ≈ 4 characters
    }

    /**
     * Get chat history for user
     */
    public function history(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $tool = Tool::where('slug', 'ai-chat')->first();
            
            if (!$tool) {
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page
                ]);
            }

            $histories = History::where('user_id', $user->id)
                ->where('tool_id', $tool->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $histories->items(),
                'total' => $histories->total(),
                'per_page' => $histories->perPage(),
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage()
            ]);

        } catch (\Exception $e) {
            Log::error('Chat History Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve chat history'
            ], 500);
        }
    }
}
