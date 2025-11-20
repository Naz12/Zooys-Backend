<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Document Intelligence Service
 * 
 * Provides semantic search, RAG-powered Q&A, and conversational chat
 * capabilities via the Doc-Service microservice.
 * 
 * Features:
 * - Document ingestion with OCR support
 * - Semantic vector search
 * - One-shot Q&A (RAG)
 * - Multi-turn conversational chat
 * - HMAC-SHA256 authentication
 * 
 * Can be used internally by other AI modules for document understanding.
 */
class DocumentIntelligenceService
{
    private string $baseUrl;
    private string $tenantId;
    private string $clientId;
    private string $keyId;
    private string $secret;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.document_intelligence.url', 'https://doc.akmicroservice.com'), '/');
        $this->tenantId = config('services.document_intelligence.tenant', 'dagu');
        $this->clientId = config('services.document_intelligence.client_id', 'dev');
        $this->keyId = config('services.document_intelligence.key_id', 'local');
        $this->secret = config('services.document_intelligence.secret', 'change_me');
        $this->timeout = config('services.document_intelligence.timeout', 120);
    }

    /**
     * Generate HMAC-SHA256 signature for authentication
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $resource Resource path (e.g., /v1/ingest)
     * @param string $query Query string (empty if none)
     * @param int $timestamp Unix timestamp
     * @return string HMAC signature (hex)
     */
    private function generateSignature(string $method, string $resource, string $query, int $timestamp): string
    {
        $base = implode('|', [
            $method,
            $resource,
            $query,
            $timestamp,
            $this->clientId,
            $this->keyId
        ]);

        $signature = hash_hmac('sha256', $base, $this->secret);

        Log::debug('Generated HMAC signature', [
            'method' => $method,
            'resource' => $resource,
            'timestamp' => $timestamp,
            'base_string' => $base,
            'signature' => $signature
        ]);

        return $signature;
    }

    /**
     * Get authenticated headers for API requests
     * 
     * @param string $method HTTP method
     * @param string $resource Resource path
     * @param string $query Query string
     * @return array Headers with HMAC authentication
     */
    private function getAuthHeaders(string $method, string $resource, string $query = ''): array
    {
        $timestamp = time();
        $signature = $this->generateSignature($method, $resource, $query, $timestamp);

        return [
            'X-Tenant-Id' => $this->tenantId,
            'X-Client-Id' => $this->clientId,
            'X-Key-Id' => $this->keyId,
            'X-Timestamp' => (string)$timestamp,
            'X-Signature' => $signature,
        ];
    }

    /**
     * Health check - verify service availability
     * 
     * Returns detailed health information including:
     * - ok: Overall health status
     * - uptime: Service uptime in seconds
     * - vector_status: Vector database status
     * - cache_status: Cache system status
     * 
     * @return array Service health status with detailed metrics
     */
    public function healthCheck(): array
    {
        $resource = '/health';
        $headers = $this->getAuthHeaders('GET', $resource);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->get($this->baseUrl . $resource);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Document Intelligence health check successful', [
                    'ok' => $data['ok'] ?? false,
                    'uptime' => $data['uptime'] ?? null,
                    'vector_status' => $data['vector_status'] ?? null,
                    'cache_status' => $data['cache_status'] ?? null
                ]);
                
                return [
                    'ok' => $data['ok'] ?? false,
                    'uptime' => $data['uptime'] ?? 0,
                    'vector_status' => $data['vector_status'] ?? 'unknown',
                    'cache_status' => $data['cache_status'] ?? 'unknown',
                    'raw' => $data // Include full response
                ];
            }

            Log::error('Document Intelligence health check failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'ok' => false,
                'uptime' => 0,
                'vector_status' => 'error',
                'cache_status' => 'error',
                'error' => 'Health check failed',
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Document Intelligence health check exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'ok' => false,
                'uptime' => 0,
                'vector_status' => 'error',
                'cache_status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ingest a document for indexing and semantic search
     * 
     * @param string $filePath Full path to the file on local storage
     * @param array $options Ingestion options
     *   - ocr: 'off'|'auto'|'force' (default: 'auto')
     *   - lang: Language code (default: 'eng')
     *   - metadata: Custom metadata array (tags, source, etc.)
     * @return array Ingestion result with doc_id and job_id
     */
    public function ingestDocument(string $filePath, array $options = []): array
    {
        $resource = '/v1/ingest';
        $headers = $this->getAuthHeaders('POST', $resource);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $ocr = $options['ocr'] ?? 'auto';
        $lang = $options['lang'] ?? 'eng';
        $metadata = $options['metadata'] ?? [];

        try {
            $request = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->asMultipart()
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->attach('ocr', $ocr)
                ->attach('lang', $lang);

            if (!empty($metadata)) {
                $request = $request->attach('metadata', json_encode($metadata));
            }

            $response = $request->post($this->baseUrl . $resource);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Document ingestion started', [
                    'file' => basename($filePath),
                    'doc_id' => $data['doc_id'] ?? null,
                    'job_id' => $data['job_id'] ?? null
                ]);
                return $data;
            }

            Log::error('Document ingestion failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \RuntimeException("Ingestion failed: {$response->body()}");
        } catch (\Exception $e) {
            Log::error('Document ingestion exception', [
                'file' => basename($filePath),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ingest text content directly (without a file)
     * 
     * @param string $text Text content to ingest
     * @param array $options Ingestion options
     *   - filename: Filename to use (default: 'summary.txt')
     *   - lang: Language code (default: 'eng')
     *   - metadata: Custom metadata array (tags, source, etc.)
     *   - force_fallback: Skip local LLM, use remote immediately (default: true)
     *   - llm_model: LLM model to use (default: 'deepseek-chat')
     * @return array Ingestion result with doc_id and job_id
     */
    public function ingestText(string $text, array $options = []): array
    {
        $resource = '/v1/ingest/text';
        $headers = $this->getAuthHeaders('POST', $resource);

        if (empty(trim($text))) {
            throw new \InvalidArgumentException('Text content cannot be empty');
        }

        $filename = $options['filename'] ?? 'summary.txt';
        $lang = $options['lang'] ?? 'eng';
        $metadata = $options['metadata'] ?? [];
        $forceFallback = $options['force_fallback'] ?? true;
        $llmModel = $options['llm_model'] ?? 'deepseek-chat';

        $payload = [
            'text' => $text,
            'filename' => $filename,
            'lang' => $lang,
            'force_fallback' => $forceFallback,
            'llm_model' => $llmModel,
        ];

        if (!empty($metadata)) {
            // Ensure metadata values are all strings or arrays of strings (no integers, no nested objects)
            $cleanMetadata = [];
            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    // Keep arrays as-is (like tags array in working example)
                    $cleanMetadata[$key] = $value;
                } elseif (is_scalar($value)) {
                    // Convert all scalar values to strings
                    $cleanMetadata[$key] = (string) $value;
                }
                // Skip null, objects, etc.
            }
            $payload['metadata'] = $cleanMetadata;
        }

        // Log the exact payload being sent for debugging
        Log::debug('DocumentIntelligenceService: Sending ingestText payload', [
            'text_length' => strlen($text),
            'filename' => $filename,
            'lang' => $lang,
            'force_fallback' => $forceFallback,
            'llm_model' => $llmModel,
            'metadata' => $payload['metadata'] ?? null,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);

        try {
            // Explicitly JSON encode the payload to ensure proper serialization
            // This matches the working direct test approach
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // Validate JSON encoding succeeded
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to encode payload as JSON: ' . json_last_error_msg());
            }
            
            // Log the exact request being sent
            Log::debug('DocumentIntelligenceService: Sending HTTP request', [
                'method' => 'POST',
                'url' => $this->baseUrl . $resource,
                'headers' => array_merge($headers, ['Content-Type' => 'application/json']),
                'body_length' => strlen($jsonPayload),
                'body_preview' => substr($jsonPayload, 0, 500),
            ]);
            
            // Try as form data (like document ingestion endpoint)
            // The service might expect form data instead of JSON
            $request = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->asMultipart()
                ->attach('text', $text)
                ->attach('filename', $filename)
                ->attach('lang', $lang)
                ->attach('force_fallback', $forceFallback ? 'true' : 'false')
                ->attach('llm_model', $llmModel);
            
            if (!empty($cleanMetadata)) {
                $request = $request->attach('metadata', json_encode($cleanMetadata));
            }
            
            $response = $request->post($this->baseUrl . $resource);
            
            // Log response details immediately
            Log::debug('DocumentIntelligenceService: Received HTTP response', [
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'response_body_preview' => substr($response->body(), 0, 500),
                'response_body_length' => strlen($response->body()),
                'response_headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                try {
                    $data = $response->json();
                    
                    // Check if response contains an error even though HTTP status is 200
                    if (isset($data['error']) || isset($data['message']) && strpos(strtolower($data['message'] ?? ''), 'error') !== false) {
                        Log::warning('DocumentIntelligenceService: Response marked as successful but contains error', [
                            'http_status' => $response->status(),
                            'response_data' => $data,
                            'response_body' => $response->body(),
                        ]);
                    }
                    
                    Log::info('Text ingestion started', [
                        'filename' => $filename,
                        'text_length' => strlen($text),
                        'doc_id' => $data['doc_id'] ?? null,
                        'job_id' => $data['job_id'] ?? null,
                        'full_response' => $data
                    ]);
                    return $data;
                } catch (\Exception $jsonException) {
                    // If JSON parsing fails, log the raw response
                    Log::error('DocumentIntelligenceService: Failed to parse successful response as JSON', [
                        'http_status' => $response->status(),
                        'response_body' => $response->body(),
                        'response_body_length' => strlen($response->body()),
                        'json_error' => $jsonException->getMessage(),
                    ]);
                    throw new \RuntimeException('Failed to parse response from Document Intelligence service: ' . $jsonException->getMessage());
                }
            }

            $statusCode = $response->status();
            $errorBody = $response->body();
            $errorData = null;
            
            // Try to parse error response as JSON
            try {
                $errorData = json_decode($errorBody, true);
            } catch (\Exception $e) {
                // If JSON parsing fails, use raw body
            }
            
            // Extract meaningful error message - ensure it's always a string
            $errorMessage = 'Unknown error';
            if ($errorData && isset($errorData['detail'])) {
                // Handle detail as array (FastAPI validation errors) or string
                if (is_array($errorData['detail'])) {
                    $errorMessage = json_encode($errorData['detail'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $errorMessage = (string) $errorData['detail'];
                }
            } elseif ($errorData && isset($errorData['error'])) {
                $errorMessage = is_array($errorData['error']) ? json_encode($errorData['error']) : (string) $errorData['error'];
            } elseif ($errorData && isset($errorData['message'])) {
                $errorMessage = is_array($errorData['message']) ? json_encode($errorData['message']) : (string) $errorData['message'];
            } elseif (!empty($errorBody)) {
                $errorMessage = (string) $errorBody;
            }
            
            // Provide descriptive error messages based on status code
            $descriptiveError = '';
            switch ($statusCode) {
                case 404:
                    $descriptiveError = "Document Intelligence service endpoint not found. The service may be misconfigured or the endpoint '/v1/ingest/text' does not exist. ";
                    break;
                case 401:
                case 403:
                    $descriptiveError = "Document Intelligence authentication failed. Please check your API credentials. ";
                    break;
                case 422:
                    $descriptiveError = "Document Intelligence validation error. The request data may be invalid. ";
                    break;
                case 500:
                case 502:
                case 503:
                    $descriptiveError = "Document Intelligence service is temporarily unavailable. Please try again later. ";
                    break;
                default:
                    $descriptiveError = "Document Intelligence service returned an error (HTTP {$statusCode}). ";
            }
            
            $fullErrorMessage = $descriptiveError . "Details: " . $errorMessage;
            
            $fullUrl = $this->baseUrl . $resource;
            
            // Enhanced error logging with full request/response details
            Log::error('Text ingestion failed - Detailed Error', [
                'http_status' => $statusCode,
                'response_body' => $errorBody,
                'response_body_length' => strlen($errorBody),
                'error_data_parsed' => $errorData,
                'error_data_keys' => $errorData ? array_keys($errorData) : [],
                'request_details' => [
                    'filename' => $filename,
                    'lang' => $lang,
                    'force_fallback' => $forceFallback,
                    'llm_model' => $llmModel,
                    'text_length' => strlen($text),
                    'text_preview' => substr($text, 0, 200),
                    'metadata' => $payload['metadata'] ?? null,
                    'metadata_type' => gettype($payload['metadata'] ?? null),
                    'metadata_keys' => !empty($payload['metadata']) ? array_keys($payload['metadata']) : [],
                    'metadata_values_types' => !empty($payload['metadata']) ? array_map('gettype', $payload['metadata']) : [],
                ],
                'payload_json' => $jsonPayload ?? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'payload_json_length' => strlen($jsonPayload ?? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                'service_config' => [
                    'base_url' => $this->baseUrl,
                    'resource' => $resource,
                    'full_url' => $fullUrl,
                    'tenant' => $this->tenantId,
                    'client_id' => $this->clientId,
                    'key_id' => $this->keyId,
                ],
                'headers_sent' => array_merge($headers, [
                    'Content-Type' => 'application/json'
                ]),
            ]);
            
            // For 404 errors, provide additional troubleshooting info
            if ($statusCode === 404) {
                $fullErrorMessage .= " Full URL attempted: {$fullUrl}. ";
                $fullErrorMessage .= "Please verify: 1) The Document Intelligence service is running, 2) The endpoint '/v1/ingest/text' exists on the microservice, 3) Your service URL configuration is correct (currently: {$this->baseUrl}).";
            }

            throw new \RuntimeException($fullErrorMessage);
        } catch (\Exception $e) {
            // Enhanced error logging with full context
            Log::error('Text ingestion exception', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'exception_code' => $e->getCode(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString(),
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 200),
                'payload_sent' => [
                    'filename' => $filename,
                    'lang' => $lang,
                    'force_fallback' => $forceFallback,
                    'llm_model' => $llmModel,
                    'metadata' => $payload['metadata'] ?? null,
                    'has_metadata' => !empty($payload['metadata']),
                    'metadata_keys' => !empty($payload['metadata']) ? array_keys($payload['metadata']) : [],
                ],
                'base_url' => $this->baseUrl,
                'resource' => $resource,
                'full_url' => $this->baseUrl . $resource,
            ]);
            throw $e;
        }
    }

    /**
     * Get job status
     * 
     * @param string $jobId Job ID from ingestion
     * @return array Job status (pending, processing, completed, failed)
     */
    public function getJobStatus(string $jobId): array
    {
        $resource = "/v1/jobs/{$jobId}";
        $headers = $this->getAuthHeaders('GET', $resource);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->get($this->baseUrl . $resource);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Job status check failed', [
                'job_id' => $jobId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'job_id' => $jobId,
                'status' => 'unknown',
                'error' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Job status check exception', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return [
                'job_id' => $jobId,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Poll job until completion (with timeout)
     * 
     * @param string $jobId Job ID to poll
     * @param int $maxAttempts Maximum polling attempts (default: 60)
     * @param int $sleepSeconds Seconds between polls (default: 2)
     * @return array Final job status
     */
    public function pollJobCompletion(string $jobId, int $maxAttempts = 60, int $sleepSeconds = 2): array
    {
        Log::info('Starting job polling', [
            'job_id' => $jobId,
            'max_attempts' => $maxAttempts,
            'sleep_seconds' => $sleepSeconds,
            'max_time_seconds' => $maxAttempts * $sleepSeconds
        ]);
        
        $lastStatus = null;
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $status = $this->getJobStatus($jobId);
                
                // Handle cases where getJobStatus returns error
                if (!isset($status['status']) || $status['status'] === 'error') {
                    Log::warning('Job status check returned error', [
                        'job_id' => $jobId,
                        'attempt' => $i + 1,
                        'status_response' => $status
                    ]);
                    
                    // If we've had multiple errors, return timeout
                    if ($i >= 10 && ($lastStatus === null || $lastStatus === 'error')) {
                        return [
                            'job_id' => $jobId,
                            'status' => 'error',
                            'error' => 'Multiple status check errors. The Document Intelligence service may be unavailable.'
                        ];
                    }
                    $lastStatus = 'error';
                    sleep($sleepSeconds);
                    continue;
                }
                
                $currentStatus = $status['status'] ?? 'unknown';
                $lastStatus = $currentStatus;
                
                // Log progress every 10 attempts or on status change
                if ($i % 10 === 0 || ($i > 0 && $currentStatus !== ($status['status'] ?? 'unknown'))) {
                    Log::info('Job polling in progress', [
                        'job_id' => $jobId,
                        'attempt' => $i + 1,
                        'max_attempts' => $maxAttempts,
                        'current_status' => $currentStatus,
                        'elapsed_seconds' => $i * $sleepSeconds
                    ]);
                }

                // Check for terminal states (Document Intelligence returns "succeeded" for completed jobs)
                if (in_array($currentStatus, ['completed', 'succeeded', 'failed', 'error'])) {
                    Log::info('Job polling completed', [
                        'job_id' => $jobId,
                        'attempts' => $i + 1,
                        'status' => $currentStatus,
                        'total_time_seconds' => $i * $sleepSeconds
                    ]);
                    return $status;
                }

                // Sleep between polls (except on last iteration)
                if ($i < $maxAttempts - 1) {
                    sleep($sleepSeconds);
                }
            } catch (\Exception $e) {
                Log::error('Job polling exception', [
                    'job_id' => $jobId,
                    'attempt' => $i + 1,
                    'error' => $e->getMessage()
                ]);
                
                // If we've had multiple exceptions, return error
                if ($i >= 5) {
                    return [
                        'job_id' => $jobId,
                        'status' => 'error',
                        'error' => 'Polling failed due to exceptions: ' . $e->getMessage()
                    ];
                }
                
                sleep($sleepSeconds);
            }
        }

        Log::warning('Job polling timeout', [
            'job_id' => $jobId,
            'max_attempts' => $maxAttempts,
            'total_time_seconds' => $maxAttempts * $sleepSeconds,
            'last_status' => $lastStatus
        ]);

        return [
            'job_id' => $jobId,
            'status' => 'timeout',
            'error' => 'Polling timeout exceeded after ' . ($maxAttempts * $sleepSeconds) . ' seconds. Last status: ' . ($lastStatus ?? 'unknown')
        ];
    }

    /**
     * Search documents semantically
     * 
     * @param string $query Search query (natural language)
     * @param array $options Search options
     *   - doc_ids: Array of doc_ids to search (optional, searches all if empty)
     *   - top_k: Number of results to return (default: 5)
     *   - filters: Additional filters (page_range, metadata, etc.)
     * @return array Search results with chunks and scores
     */
    public function search(string $query, array $options = []): array
    {
        $resource = '/v1/search';
        $headers = $this->getAuthHeaders('POST', $resource);

        $payload = [
            'query' => $query,
            'top_k' => $options['top_k'] ?? 5,
        ];

        if (!empty($options['doc_ids'])) {
            $payload['doc_ids'] = $options['doc_ids'];
        }

        if (!empty($options['filters'])) {
            $payload['filters'] = $options['filters'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->baseUrl . $resource, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Document search completed', [
                    'query' => $query,
                    'result_count' => count($data['results'] ?? [])
                ]);
                return $data;
            }

            Log::error('Document search failed', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \RuntimeException("Search failed: {$response->body()}");
        } catch (\Exception $e) {
            Log::error('Document search exception', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ask a question and get a RAG-powered answer (one-shot)
     * 
     * @param string $query Question to ask
     * @param array $options Answer options
     *   - doc_ids: Array of doc_ids to search (required)
     *   - llm_model: LLM to use (default: 'llama3', also supports 'deepseek-chat')
     *   - max_tokens: Maximum tokens in response (default: 512)
     *   - top_k: Number of context chunks (default: 3)
     *   - temperature: LLM temperature (default: 0.7)
     *   - force_fallback: Skip local LLM, use remote immediately (default: true)
     *   - filters: Additional filters (page_range, metadata, etc.)
     * @return array Answer with sources
     */
    public function answer(string $query, array $options = []): array
    {
        if (empty($options['doc_ids'])) {
            throw new \InvalidArgumentException('doc_ids are required for answer');
        }

        $resource = '/v1/answer';
        $headers = $this->getAuthHeaders('POST', $resource);

        $payload = [
            'query' => $query,
            'doc_ids' => $options['doc_ids'],
            'llm_model' => $options['llm_model'] ?? 'llama3',
            'max_tokens' => $options['max_tokens'] ?? 512,
            'top_k' => $options['top_k'] ?? 3,
            'temperature' => $options['temperature'] ?? 0.7,
            'force_fallback' => true, // Always true for Document Intelligence microservice
        ];

        if (!empty($options['filters'])) {
            $payload['filters'] = $options['filters'];
        }

        try {
            // Log the request details for debugging
            Log::info('Document answer request', [
                'url' => $this->baseUrl . $resource,
                'method' => 'POST',
                'payload' => $payload,
                'headers_keys' => array_keys($headers), // Don't log sensitive headers
                'timeout' => $this->timeout
            ]);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->baseUrl . $resource, $payload);
            
            // Log response status immediately
            Log::info('Document answer HTTP response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'has_body' => !empty($response->body())
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log the full response structure for debugging
                Log::info('Document answer response received', [
                    'query' => $query,
                    'status' => $response->status(),
                    'response_keys' => array_keys($data),
                    'has_answer' => isset($data['answer']),
                    'has_error' => isset($data['error']),
                    'has_sources' => isset($data['sources']),
                    'answer_length' => isset($data['answer']) ? strlen($data['answer']) : 0,
                    'sources_count' => isset($data['sources']) ? count($data['sources']) : 0
                ]);
                
                // Check if response indicates LLM service unavailable even with 200 status
                if (isset($data['error']) && (
                    stripos($data['error'], 'unavailable') !== false ||
                    stripos($data['error'], 'LLM service') !== false
                )) {
                    Log::warning('Document answer returned error in successful response', [
                        'query' => $query,
                        'error' => $data['error'],
                        'full_response' => $data
                    ]);
                    return ['error' => $data['error']];
                }
                
                // If there's an error field but also an answer, prioritize the answer (some services return both)
                if (isset($data['error']) && !isset($data['answer'])) {
                    // Only treat as error if there's no answer field
                    Log::warning('Document answer has error field but no answer', [
                        'query' => $query,
                        'error' => $data['error'],
                        'full_response' => $data
                    ]);
                    return ['error' => $data['error']];
                }
                
                // If we have an answer, return it even if there's also an error field
                if (isset($data['answer']) && !empty($data['answer'])) {
                    // Success - we have an answer, ignore any error field
                    return $data;
                }
                
                Log::info('Document answer generated successfully', [
                    'query' => $query,
                    'answer_length' => strlen($data['answer'] ?? ''),
                    'sources_count' => count($data['sources'] ?? [])
                ]);
                return $data;
            }

            $statusCode = $response->status();
            $errorBody = $response->body();
            $errorData = null;
            try {
                $errorData = $response->json();
            } catch (\Exception $e) {
                // If JSON parsing fails, use raw body
            }
            
            Log::error('Document answer failed', [
                'query' => $query,
                'status' => $statusCode,
                'body' => $errorBody,
                'error_data' => $errorData
            ]);
            
            // Extract error message from response
            $errorMessage = 'Answer generation failed';
            if ($errorData && isset($errorData['detail'])) {
                $errorMessage = $errorData['detail'];
            } elseif ($errorData && isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            } elseif ($errorData && isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } else {
                $errorMessage = $errorBody ?? 'Unknown error';
            }
            
            // Check for service unavailability indicators
            // Only treat as unavailable if it's a server error (5xx) or explicitly mentions unavailability
            // Don't treat all errors as unavailable - some might be validation errors, etc.
            $isServiceUnavailable = in_array($statusCode, [500, 502, 503, 504]) &&
                (stripos($errorMessage, 'unavailable') !== false ||
                 stripos($errorMessage, 'LLM service') !== false ||
                 stripos($errorMessage, 'service unavailable') !== false);
            
            if ($isServiceUnavailable) {
                throw new \RuntimeException('LLM service unavailable (fallback)');
            }

            // For other errors, throw the actual error message
            throw new \RuntimeException($errorMessage);
        } catch (\Exception $e) {
            // Check if it's a timeout or connection error
            $isTimeout = strpos($e->getMessage(), 'timeout') !== false || 
                        strpos($e->getMessage(), 'timed out') !== false ||
                        strpos($e->getMessage(), 'Connection timed out') !== false;
            $isConnectionError = strpos($e->getMessage(), 'Connection') !== false ||
                                strpos($e->getMessage(), 'Failed to connect') !== false ||
                                strpos($e->getMessage(), 'cURL error') !== false;
            
            Log::error('Document answer exception', [
                'query' => $query,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'is_timeout' => $isTimeout,
                'is_connection_error' => $isConnectionError,
                'url' => $this->baseUrl . $resource,
                'payload' => $payload
            ]);
            
            // If it's a timeout or connection error, don't treat as LLM unavailable
            if ($isTimeout || $isConnectionError) {
                throw new \RuntimeException('Document Intelligence service connection error: ' . $e->getMessage());
            }
            
            throw $e;
        }
    }

    /**
     * Chat with documents (multi-turn conversation with memory)
     * 
     * Supports multi-turn conversations by maintaining context via conversation_id.
     * The microservice remembers previous messages in the same conversation.
     * 
     * @param string $query Current question/message
     * @param array $options Chat options
     *   - conversation_id: ID to maintain context (optional, auto-generated if empty)
     *   - doc_ids: Array of doc_ids to search (required)
     *   - llm_model: LLM to use (default: 'llama3', options: 'deepseek-chat', 'mistral:latest', 'gpt-4')
     *   - max_tokens: Maximum tokens in response (default: 512)
     *   - top_k: Number of context chunks (default: 3)
     *   - force_fallback: Skip local LLM, use remote immediately (default: true)
     *   - filters: Additional filters (page_range, metadata, etc.)
     * @return array Chat response with conversation_id, answer, and sources
     */
    public function chat(string $query, array $options = []): array
    {
        if (empty($options['doc_ids'])) {
            throw new \InvalidArgumentException('doc_ids are required for chat');
        }

        $resource = '/v1/chat';
        $headers = $this->getAuthHeaders('POST', $resource);

        $payload = [
            'query' => $query,
            'doc_ids' => $options['doc_ids'],
            'llm_model' => $options['llm_model'] ?? 'llama3',
            'max_tokens' => $options['max_tokens'] ?? 512,
            'top_k' => $options['top_k'] ?? 3,
            'temperature' => $options['temperature'] ?? 0.7,
            'force_fallback' => true, // Always true for Document Intelligence microservice
        ];

        if (!empty($options['conversation_id'])) {
            $payload['conversation_id'] = $options['conversation_id'];
        }

        if (!empty($options['filters'])) {
            $payload['filters'] = $options['filters'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->baseUrl . $resource, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Document chat response', [
                    'query' => $query,
                    'conversation_id' => $data['conversation_id'] ?? null,
                    'answer_length' => strlen($data['answer'] ?? ''),
                    'sources_count' => count($data['sources'] ?? [])
                ]);
                return $data;
            }

            Log::error('Document chat failed', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \RuntimeException("Chat failed: {$response->body()}");
        } catch (\Exception $e) {
            Log::error('Document chat exception', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ingest document from file_id (integrates with your file upload system)
     * 
     * This is a helper method for internal use by other modules
     * 
     * @param int $fileId File upload ID from your database
     * @param array $options Ingestion options
     * @return array Ingestion result
     */
    public function ingestFromFileId(int $fileId, array $options = []): array
    {
        // Get file from your file upload system
        $fileUpload = \App\Models\FileUpload::findOrFail($fileId);
        $filePath = storage_path('app/' . $fileUpload->file_path);

        // Add file metadata
        $metadata = array_merge($options['metadata'] ?? [], [
            'file_id' => $fileId,
            'user_id' => $fileUpload->user_id,
            'original_name' => $fileUpload->original_name,
            'uploaded_at' => $fileUpload->created_at->toISOString(),
        ]);

        $options['metadata'] = $metadata;

        return $this->ingestDocument($filePath, $options);
    }

    /**
     * Check if the service is available
     * 
     * @return bool True if service is healthy
     */
    public function isAvailable(): bool
    {
        try {
            $health = $this->healthCheck();
            return $health['ok'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

