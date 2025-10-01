<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\Tool;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
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
            $conversationHistory = $request->input('conversation_history', []);
            $model = $request->input('model', 'gpt-3.5-turbo');
            $temperature = $request->input('temperature', 0.7);
            $maxTokens = $request->input('max_tokens', 1000);

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
     * Generate AI response using OpenAI
     */
    private function generateAIResponse($messages, $model, $temperature, $maxTokens)
    {
        try {
            $apiKey = config('services.openai.api_key');
            $url = config('services.openai.url');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => (int) $maxTokens,
                'temperature' => (float) $temperature,
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error: ' . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
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
