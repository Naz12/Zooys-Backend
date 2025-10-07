<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use App\Models\DocumentMetadata;
use App\Models\History;
use App\Models\Tool;
use App\Services\VectorDatabaseService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentChatController extends Controller
{
    private $vectorService;
    private $openAIService;
    
    public function __construct(VectorDatabaseService $vectorService, OpenAIService $openAIService)
    {
        $this->vectorService = $vectorService;
        $this->openAIService = $openAIService;
    }
    
    /**
     * Chat with document
     */
    public function chat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'document_id' => 'required|exists:file_uploads,id',
                'query' => 'required|string|max:1000',
                'conversation_history' => 'nullable|array',
                'conversation_history.*.role' => 'required|string|in:user,assistant',
                'conversation_history.*.content' => 'required|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }
            
            $documentId = $request->input('document_id');
            $query = $request->input('query');
            $history = $request->input('conversation_history', []);
            
            // Verify document belongs to user
            $document = FileUpload::where('id', $documentId)
                ->where('user_id', $request->user()->id)
                ->first();
            
            if (!$document) {
                return response()->json([
                    'error' => 'Document not found or access denied'
                ], 404);
            }
            
            // Check if document is processed
            $status = $this->vectorService->getDocumentStatus($documentId);
            if ($status['status'] !== 'completed') {
                return response()->json([
                    'error' => 'Document is not ready for chat. Please wait for processing to complete.',
                    'status' => $status['status']
                ], 400);
            }
            
            // Search for relevant chunks
            $relevantChunks = $this->vectorService->searchSimilarChunks($query, $documentId, 5);
            
            if (empty($relevantChunks)) {
                return response()->json([
                    'answer' => 'I couldn\'t find relevant information in the document to answer your question.',
                    'sources' => [],
                    'metadata' => [
                        'document_id' => $documentId,
                        'processing_time' => '0.5s',
                        'tokens_used' => 0
                    ]
                ]);
            }
            
            // Generate answer with sources
            $answer = $this->generateAnswer($query, $relevantChunks, $history);
            
            // Store in history
            $this->storeChatHistory($request->user(), $documentId, $query, $answer);
            
            return response()->json([
                'answer' => $answer['text'],
                'sources' => $answer['sources'],
                'metadata' => [
                    'document_id' => $documentId,
                    'processing_time' => $answer['processing_time'],
                    'tokens_used' => $answer['tokens_used']
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Document chat error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process your question at this time'
            ], 500);
        }
    }
    
    /**
     * Get chat history for a document
     */
    public function history(Request $request, $documentId)
    {
        try {
            // Verify document belongs to user
            $document = FileUpload::where('id', $documentId)
                ->where('user_id', $request->user()->id)
                ->first();
            
            if (!$document) {
                return response()->json([
                    'error' => 'Document not found or access denied'
                ], 404);
            }
            
            // Get chat history from database
            $history = History::where('user_id', $request->user()->id)
                ->where('meta->document_id', $documentId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            
            $formattedHistory = $history->map(function($item) {
                return [
                    'id' => $item->id,
                    'query' => json_decode($item->input, true)['query'] ?? '',
                    'answer' => $item->output,
                    'timestamp' => $item->created_at
                ];
            });
            
            return response()->json([
                'document_id' => $documentId,
                'history' => $formattedHistory
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chat history error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to retrieve chat history'
            ], 500);
        }
    }
    
    /**
     * Generate answer with sources
     */
    private function generateAnswer($query, $chunks, $history)
    {
        $startTime = microtime(true);
        
        // Build context from chunks
        $context = $this->buildContext($chunks);
        
        // Build conversation history
        $conversationHistory = $this->buildConversationHistory($history);
        
        // Generate prompt
        $prompt = $this->buildChatPrompt($query, $context, $conversationHistory);
        
        // Get AI response
        $response = $this->openAIService->generateResponse($prompt);
        
        $processingTime = round((microtime(true) - $startTime) * 1000) / 1000;
        
        return [
            'text' => $response,
            'sources' => $this->formatSources($chunks),
            'processing_time' => $processingTime . 's',
            'tokens_used' => $this->estimateTokens($prompt . $response)
        ];
    }
    
    /**
     * Build context from chunks
     */
    private function buildContext($chunks)
    {
        $context = '';
        foreach ($chunks as $chunk) {
            $context .= "Page {$chunk['page']}: {$chunk['text']}\n\n";
        }
        return $context;
    }
    
    /**
     * Build conversation history
     */
    private function buildConversationHistory($history)
    {
        $conversation = '';
        foreach ($history as $message) {
            $role = $message['role'] === 'user' ? 'User' : 'Assistant';
            $conversation .= "{$role}: {$message['content']}\n";
        }
        return $conversation;
    }
    
    /**
     * Build chat prompt
     */
    private function buildChatPrompt($query, $context, $conversationHistory)
    {
        $prompt = "You are an AI assistant that answers questions based on document content. ";
        $prompt .= "Use only the information provided in the context to answer questions. ";
        $prompt .= "Always cite the page numbers when referencing information.\n\n";
        
        if (!empty($conversationHistory)) {
            $prompt .= "Previous conversation:\n{$conversationHistory}\n\n";
        }
        
        $prompt .= "Document context:\n{$context}\n\n";
        $prompt .= "Question: {$query}\n\n";
        $prompt .= "Answer:";
        
        return $prompt;
    }
    
    /**
     * Format sources for response
     */
    private function formatSources($chunks)
    {
        $sources = [];
        foreach ($chunks as $chunk) {
            $sources[] = [
                'page' => $chunk['page'],
                'text' => substr($chunk['text'], 0, 200) . '...',
                'similarity' => round($chunk['similarity'] * 100, 2) . '%'
            ];
        }
        return $sources;
    }
    
    /**
     * Store chat history
     */
    private function storeChatHistory($user, $documentId, $query, $answer)
    {
        try {
            $tool = Tool::firstOrCreate(
                ['slug' => 'document_chat'],
                [
                    'name' => 'Document Chat',
                    'enabled' => true
                ]
            );
            
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input' => json_encode([
                    'document_id' => $documentId,
                    'query' => $query
                ]),
                'output' => $answer['text'],
                'meta' => json_encode([
                    'document_id' => $documentId,
                    'sources' => $answer['sources'],
                    'processing_time' => $answer['processing_time'],
                    'tokens_used' => $answer['tokens_used']
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store chat history: ' . $e->getMessage());
        }
    }
    
    /**
     * Estimate token count
     */
    private function estimateTokens($text)
    {
        return ceil(strlen($text) / 4); // Rough estimation: 1 token ≈ 4 characters
    }
}
