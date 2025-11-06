<?php

namespace App\Services\Modules;

use App\Services\DocumentIntelligenceService;
use Illuminate\Support\Facades\Log;

class DocumentIntelligenceModule
{
    private $docIntelligenceService;

    public function __construct(DocumentIntelligenceService $docIntelligenceService)
    {
        $this->docIntelligenceService = $docIntelligenceService;
    }

    /**
     * Ingest a document for indexing and semantic search
     * 
     * @param string $filePath Full path to the file
     * @param array $options Ingestion options
     * @return array Ingestion result with doc_id and job_id
     */
    public function ingestDocument(string $filePath, array $options = [])
    {
        try {
            Log::info('DocumentIntelligenceModule: Starting document ingestion', [
                'file' => basename($filePath),
                'options' => $options
            ]);

            $result = $this->docIntelligenceService->ingestDocument($filePath, $options);

            Log::info('DocumentIntelligenceModule: Document ingestion started', [
                'doc_id' => $result['doc_id'] ?? null,
                'job_id' => $result['job_id'] ?? null
            ]);

            return [
                'success' => true,
                'doc_id' => $result['doc_id'] ?? null,
                'job_id' => $result['job_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Document ingestion failed', [
                'error' => $e->getMessage(),
                'file' => basename($filePath)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search documents semantically
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function search(string $query, array $options = [])
    {
        try {
            Log::info('DocumentIntelligenceModule: Starting semantic search', [
                'query' => $query,
                'options' => $options
            ]);

            $result = $this->docIntelligenceService->search($query, $options);

            Log::info('DocumentIntelligenceModule: Search completed', [
                'query' => $query,
                'result_count' => count($result['results'] ?? [])
            ]);

            return [
                'success' => true,
                'results' => $result['results'] ?? [],
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Search failed', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ask a question and get a RAG-powered answer (one-shot)
     * 
     * @param string $query Question to ask
     * @param array $options Answer options
     * @return array Answer with sources
     */
    public function answer(string $query, array $options = [])
    {
        try {
            Log::info('DocumentIntelligenceModule: Generating answer', [
                'query' => $query,
                'doc_ids' => $options['doc_ids'] ?? [],
                'llm_model' => $options['llm_model'] ?? 'llama3'
            ]);

            // Ensure force_fallback is always true
            $options['force_fallback'] = true;
            $result = $this->docIntelligenceService->answer($query, $options);

            // Check if there's an error in the result
            if (isset($result['error'])) {
                Log::warning('DocumentIntelligenceModule: Answer returned error', [
                    'query' => $query,
                    'error' => $result['error']
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'],
                    'data' => $result
                ];
            }

            Log::info('DocumentIntelligenceModule: Answer generated successfully', [
                'query' => $query,
                'answer_length' => strlen($result['answer'] ?? ''),
                'sources_count' => count($result['sources'] ?? [])
            ]);

            return [
                'success' => true,
                'answer' => $result['answer'] ?? '',
                'sources' => $result['sources'] ?? [],
                'conversation_id' => $result['conversation_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Answer generation failed', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Chat with documents (multi-turn conversation)
     * 
     * @param string $query Current question/message
     * @param array $options Chat options
     * @return array Chat response
     */
    public function chat(string $query, array $options = [])
    {
        try {
            Log::info('DocumentIntelligenceModule: Processing chat message', [
                'query' => $query,
                'conversation_id' => $options['conversation_id'] ?? null,
                'doc_ids' => $options['doc_ids'] ?? [],
                'llm_model' => $options['llm_model'] ?? 'llama3'
            ]);

            // Ensure force_fallback is always true
            $options['force_fallback'] = true;
            $result = $this->docIntelligenceService->chat($query, $options);

            Log::info('DocumentIntelligenceModule: Chat response generated', [
                'query' => $query,
                'conversation_id' => $result['conversation_id'] ?? null,
                'answer_length' => strlen($result['answer'] ?? ''),
                'sources_count' => count($result['sources'] ?? [])
            ]);

            return [
                'success' => true,
                'answer' => $result['answer'] ?? '',
                'sources' => $result['sources'] ?? [],
                'conversation_id' => $result['conversation_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Chat failed', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Poll job until completion
     * 
     * @param string $jobId Job ID to poll
     * @param int $maxAttempts Maximum polling attempts
     * @param int $sleepSeconds Seconds between polls
     * @return array Final job status
     */
    public function pollJobCompletion(string $jobId, int $maxAttempts = 60, int $sleepSeconds = 2)
    {
        try {
            Log::info('DocumentIntelligenceModule: Starting job polling', [
                'job_id' => $jobId,
                'max_attempts' => $maxAttempts
            ]);

            $result = $this->docIntelligenceService->pollJobCompletion($jobId, $maxAttempts, $sleepSeconds);

            return [
                'success' => in_array($result['status'] ?? 'unknown', ['completed', 'failed', 'error', 'timeout']),
                'status' => $result['status'] ?? 'unknown',
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Job polling failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get job status
     * 
     * @param string $jobId Job ID
     * @return array Job status
     */
    public function getJobStatus(string $jobId)
    {
        try {
            $result = $this->docIntelligenceService->getJobStatus($jobId);

            return [
                'success' => true,
                'status' => $result['status'] ?? 'unknown',
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Get job status failed', [
                'error' => $e->getMessage(),
                'job_id' => $jobId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Health check
     * 
     * @return array Health status
     */
    public function healthCheck()
    {
        try {
            $result = $this->docIntelligenceService->healthCheck();

            return [
                'success' => $result['ok'] ?? false,
                'ok' => $result['ok'] ?? false,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ingest document from file_id
     * 
     * @param int $fileId File upload ID
     * @param array $options Ingestion options
     * @return array Ingestion result
     */
    public function ingestFromFileId(int $fileId, array $options = [])
    {
        try {
            Log::info('DocumentIntelligenceModule: Ingesting from file_id', [
                'file_id' => $fileId,
                'options' => $options
            ]);

            $result = $this->docIntelligenceService->ingestFromFileId($fileId, $options);

            return [
                'success' => true,
                'doc_id' => $result['doc_id'] ?? null,
                'job_id' => $result['job_id'] ?? null,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('DocumentIntelligenceModule: Ingest from file_id failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

