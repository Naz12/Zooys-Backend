<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\UniversalJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Document Intelligence Controller
 * 
 * Provides endpoints for document ingestion, semantic search, RAG-powered Q&A,
 * and conversational chat capabilities.
 * 
 * All operations are asynchronous and use the Universal Job Service for tracking.
 */
class DocumentIntelligenceController extends Controller
{
    private $universalJobService;

    public function __construct(UniversalJobService $universalJobService)
    {
        $this->universalJobService = $universalJobService;
    }

    /**
     * Ingest a document for semantic indexing
     * 
     * POST /api/documents/ingest
     * 
     * Body:
     * {
     *   "file_id": 123,
     *   "ocr": "auto",           // optional: off|auto|force
     *   "language": "eng",       // optional: language code
     *   "metadata": {            // optional: custom metadata
     *     "tags": ["contract", "legal"],
     *     "source": "client_upload"
     *   }
     * }
     */
    public function ingest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|integer|exists:file_uploads,id',
            'ocr' => 'nullable|in:off,auto,force',
            'language' => 'nullable|string|max:10',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            $job = $this->universalJobService->createJob(
                'document_intelligence',
                [
                    'action' => 'ingest',
                    'file_id' => $request->input('file_id'),
                    'params' => [
                        'ocr' => $request->input('ocr', 'auto'),
                        'lang' => $request->input('language', 'eng'),
                        'metadata' => $request->input('metadata', [])
                    ]
                ],
                [],
                $userId
            );

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Document ingestion started',
                'job_id' => $job['id'],
                'status' => 'pending'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Document ingestion request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start document ingestion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ingest text content directly (without a file)
     * 
     * POST /api/documents/ingest/text
     * 
     * Body:
     * {
     *   "text": "Summarized PDF text or any prepared content goes here.",
     *   "filename": "summary.txt",        // optional: default "summary.txt"
     *   "language": "eng",                // optional: language code
     *   "llm_model": "llama3",            // optional: LLM model
     *   "force_fallback": true,           // optional: skip local LLM
     *   "metadata": {                     // optional: custom metadata
     *     "tags": ["summary", "external"],
     *     "business_unit": "ops"
     *   }
     * }
     */
    public function ingestText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:1',
            'filename' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:10',
            'llm_model' => 'nullable|string|max:50',
            'force_fallback' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            $job = $this->universalJobService->createJob(
                'document_intelligence',
                [
                    'action' => 'ingest_text',
                    'text' => $request->input('text'),
                    'params' => [
                        'filename' => $request->input('filename', 'summary.txt'),
                        'lang' => $request->input('language', 'eng'),
                        'llm_model' => $request->input('llm_model', 'llama3'),
                        'force_fallback' => $request->input('force_fallback', true),
                        'metadata' => $request->input('metadata', [])
                    ]
                ],
                [],
                $userId
            );

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Text ingestion started',
                'job_id' => $job['id'],
                'status' => 'pending'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Text ingestion request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start text ingestion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Semantic search across documents
     * 
     * POST /api/documents/search
     * 
     * Body:
     * {
     *   "query": "What is the contract value?",
     *   "doc_ids": ["doc_abc123"],      // optional: filter by specific documents
     *   "top_k": 5,                     // optional: number of results
     *   "filters": {                    // optional: additional filters
     *     "page_range": [1, 10]
     *   }
     * }
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'doc_ids' => 'nullable|array',
            'doc_ids.*' => 'string',
            'top_k' => 'nullable|integer|min:1|max:20',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            $job = $this->universalJobService->createJob(
                'document_intelligence',
                [
                    'action' => 'search',
                    'query' => $request->input('query'),
                    'doc_ids' => $request->input('doc_ids', []),
                    'params' => [
                        'top_k' => $request->input('top_k', 5),
                        'filters' => $request->input('filters', [])
                    ]
                ],
                [],
                $userId
            );

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Search started',
                'job_id' => $job['id'],
                'status' => 'pending'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Document search request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start document search',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ask a question and get RAG-powered answer (one-shot)
     * 
     * POST /api/documents/answer
     * 
     * Body:
     * {
     *   "query": "What are the key terms?",
     *   "doc_ids": ["doc_abc123"],      // required: documents to search
     *   "llm_model": "llama3",          // optional: LLM to use
     *   "max_tokens": 512,              // optional: max response tokens
     *   "top_k": 3,                     // optional: context chunks
     *   "temperature": 0.7,             // optional: LLM temperature
     *   "force_fallback": false,        // optional: skip local LLM, use remote
     *   "filters": {                    // optional: additional filters
     *     "page_range": [1, 10]
     *   }
     * }
     */
    public function answer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'doc_ids' => 'required|array|min:1',
            'doc_ids.*' => 'string',
            'llm_model' => 'nullable|string|max:50',
            'max_tokens' => 'nullable|integer|min:50|max:2000',
            'top_k' => 'nullable|integer|min:1|max:10',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'force_fallback' => 'nullable|boolean',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            $job = $this->universalJobService->createJob(
                'document_intelligence',
                [
                    'action' => 'answer',
                    'query' => $request->input('query'),
                    'doc_ids' => $request->input('doc_ids'),
                    'params' => [
                        'llm_model' => $request->input('llm_model', 'llama3'),
                        'max_tokens' => $request->input('max_tokens', 512),
                        'top_k' => $request->input('top_k', 3),
                        'temperature' => $request->input('temperature'),
                        'force_fallback' => $request->input('force_fallback', false),
                        'filters' => $request->input('filters', [])
                    ]
                ],
                [],
                $userId
            );

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Answer generation started',
                'job_id' => $job['id'],
                'status' => 'pending'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Document answer request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start answer generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chat with documents (multi-turn conversation with memory)
     * 
     * POST /api/documents/chat
     * 
     * Body:
     * {
     *   "query": "Summarize section 3",
     *   "doc_ids": ["doc_abc123"],           // required: documents to chat with
     *   "conversation_id": "conv_xyz",       // optional: maintain context across turns
     *   "llm_model": "mistral:latest",       // optional: LLM to use
     *   "max_tokens": 512,                   // optional: max response tokens
     *   "top_k": 3,                          // optional: context chunks
     *   "force_fallback": false,             // optional: skip local LLM, use remote
     *   "filters": {                         // optional: additional filters
     *     "page_range": [1, 10]
     *   }
     * }
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'doc_ids' => 'required|array|min:1',
            'doc_ids.*' => 'string',
            'conversation_id' => 'nullable|string|max:100',
            'llm_model' => 'nullable|string|max:50',
            'max_tokens' => 'nullable|integer|min:50|max:2000',
            'top_k' => 'nullable|integer|min:1|max:10',
            'force_fallback' => 'nullable|boolean',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            $job = $this->universalJobService->createJob(
                'document_intelligence',
                [
                    'action' => 'chat',
                    'query' => $request->input('query'),
                    'doc_ids' => $request->input('doc_ids'),
                    'params' => [
                        'conversation_id' => $request->input('conversation_id'),
                        'llm_model' => $request->input('llm_model', 'llama3'),
                        'max_tokens' => $request->input('max_tokens', 512),
                        'top_k' => $request->input('top_k', 3),
                        'force_fallback' => $request->input('force_fallback', false),
                        'filters' => $request->input('filters', [])
                    ]
                ],
                [],
                $userId
            );

            // Queue the job for processing
            $this->universalJobService->queueJob($job['id']);

            return response()->json([
                'success' => true,
                'message' => 'Chat started',
                'job_id' => $job['id'],
                'status' => 'pending'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Document chat request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job status
     * 
     * GET /api/documents/jobs/{job_id}/status
     */
    public function getStatus(Request $request, string $jobId)
    {
        try {
            $job = $this->universalJobService->getJob($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Check if user owns this job
            $userId = auth()->id();
            if ($job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'status' => $job['status'],
                'stage' => $job['stage'],
                'progress' => $job['progress'],
                'created_at' => $job['created_at'],
                'updated_at' => $job['updated_at'],
                'metadata' => [
                    'remote_job_id' => $job['metadata']['remote_job_id'] ?? null,
                    'doc_id' => $job['metadata']['doc_id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job result
     * 
     * GET /api/documents/jobs/{job_id}/result
     */
    public function getResult(Request $request, string $jobId)
    {
        try {
            $job = $this->universalJobService->getJob($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Check if user owns this job
            $userId = auth()->id();
            if ($job['user_id'] !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($job['status'] !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not completed yet',
                    'status' => $job['status'],
                    'progress' => $job['progress']
                ], 202);
            }

            return response()->json([
                'success' => true,
                'job_id' => $job['id'],
                'result' => $job['result'],
                'metadata' => [
                    'processing_time' => $job['metadata']['total_processing_time'] ?? null,
                    'completed_at' => $job['metadata']['processing_completed_at'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get job result', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check - verify doc-service availability
     * 
     * GET /api/documents/health
     * 
     * Returns:
     * {
     *   "success": true,
     *   "service": "document-intelligence",
     *   "ok": true,
     *   "uptime": 123456,
     *   "vector_status": "healthy",
     *   "cache_status": "healthy"
     * }
     */
    public function health(Request $request)
    {
        try {
            $docService = app(\App\Services\DocumentIntelligenceService::class);
            $health = $docService->healthCheck();

            return response()->json([
                'success' => $health['ok'] ?? false,
                'service' => 'document-intelligence',
                'ok' => $health['ok'] ?? false,
                'uptime' => $health['uptime'] ?? 0,
                'vector_status' => $health['vector_status'] ?? 'unknown',
                'cache_status' => $health['cache_status'] ?? 'unknown',
                'raw_health' => $health
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'service' => 'document-intelligence',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

