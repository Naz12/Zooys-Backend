<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UniversalJobService
{
    private $universalFileModule;
    private $aiResultService;

    public function __construct(
        \App\Services\Modules\UniversalFileManagementModule $universalFileModule,
        AIResultService $aiResultService
    ) {
        $this->universalFileModule = $universalFileModule;
        $this->aiResultService = $aiResultService;
    }

    /**
     * Create a new universal job
     */
    public function createJob($toolType, $input, $options = [], $userId = null)
    {
        $jobId = Str::uuid()->toString();
        
        $job = [
            'id' => $jobId,
            'tool_type' => $toolType,
            'input' => $input,
            'options' => $options,
            'user_id' => $userId,
            'status' => 'pending',
            'stage' => 'initializing',
            'progress' => 0,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'logs' => [],
            'result' => null,
            'error' => null,
            'metadata' => [
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'total_processing_time' => null,
                'file_count' => 0,
                'tokens_used' => 0,
                'confidence_score' => 0.0
            ]
        ];

        // Store job in cache
        Cache::put("universal_job_{$jobId}", $job, 7200); // 2 hours TTL
        
        Log::info("Universal job created", [
            'job_id' => $jobId,
            'tool_type' => $toolType,
            'user_id' => $userId
        ]);

        return $job;
    }

    /**
     * Get job status
     */
    public function getJob($jobId)
    {
        return Cache::get("universal_job_{$jobId}");
    }

    /**
     * Update job status
     */
    public function updateJob($jobId, $updates)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job = array_merge($job, $updates);
        $job['updated_at'] = now()->toISOString();
        
        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        return true;
    }

    /**
     * Add log entry to job
     */
    public function addLog($jobId, $message, $level = 'info', $data = [])
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $logEntry = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'data' => $data
        ];

        $job['logs'][] = $logEntry;
        $job['updated_at'] = now()->toISOString();
        
        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        Log::log($level, "Universal Job {$jobId}: {$message}", $data);
        
        return true;
    }

    /**
     * Complete job with result
     */
    public function completeJob($jobId, $result, $metadata = [])
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job['status'] = 'completed';
        $job['stage'] = 'completed';
        $job['progress'] = 100;
        $job['result'] = $result;
        $job['updated_at'] = now()->toISOString();
        $job['metadata'] = array_merge($job['metadata'], $metadata, [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateProcessingTime($job)
        ]);

        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        $this->addLog($jobId, 'Job completed successfully', 'info');
        
        return true;
    }

    /**
     * Fail job with error
     */
    public function failJob($jobId, $error, $metadata = [])
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return false;
        }

        $job['status'] = 'failed';
        $job['stage'] = 'failed';
        $job['error'] = $error;
        $job['updated_at'] = now()->toISOString();
        $job['metadata'] = array_merge($job['metadata'], $metadata, [
            'processing_completed_at' => now()->toISOString(),
            'total_processing_time' => $this->calculateProcessingTime($job)
        ]);

        Cache::put("universal_job_{$jobId}", $job, 7200);
        
        $this->addLog($jobId, "Job failed: {$error}", 'error');
        
        return true;
    }

    /**
     * Process a universal job with detailed stage tracking
     */
    public function processJob($jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            throw new \Exception("Job not found: {$jobId}");
        }

        try {
            $this->updateJob($jobId, [
                'status' => 'running',
                'stage' => 'initializing',
                'progress' => 5,
                'metadata' => array_merge($job['metadata'], [
                    'processing_started_at' => now()->toISOString()
                ])
            ]);

            $this->addLog($jobId, "Starting {$job['tool_type']} processing", 'info', [
                'tool_type' => $job['tool_type'],
                'content_type' => $job['input']['content_type'] ?? 'unknown'
            ]);

            // Set a maximum processing time
            $maxProcessingTime = 900; // 15 minutes for Smartproxy
            $startTime = time();
            
            $result = $this->processByToolTypeWithStages($job, $job['user_id']);
            
            // Check if processing took too long
            $processingTime = time() - $startTime;
            if ($processingTime > $maxProcessingTime) {
                $this->failJob($jobId, "Job processing timeout after {$processingTime} seconds");
                return ['success' => false, 'error' => 'Processing timeout'];
            }
            
            if ($result['success']) {
                $this->completeJob($jobId, $result['data'], $result['metadata'] ?? []);
            } else {
                $this->failJob($jobId, $result['error'], $result['metadata'] ?? []);
            }

            return $result;

        } catch (\Exception $e) {
            $this->failJob($jobId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process job based on tool type with detailed stage tracking
     */
    private function processByToolTypeWithStages($job, $userId = null)
    {
        $toolType = $job['tool_type'];
        $input = $job['input'];
        $options = $job['options'];

        switch ($toolType) {
            case 'summarize':
                return $this->processSummarizeJobWithStages($job['id'], $input, $options, $userId);
            
            case 'math':
                return $this->processMathJobWithStages($job['id'], $input, $options);
            
            case 'flashcards':
                return $this->processFlashcardsJobWithStages($job['id'], $input, $options);
            
            case 'presentations':
                return $this->processPresentationsJobWithStages($job['id'], $input, $options);
            
            case 'document_chat':
                return $this->processDocumentChatJobWithStages($job['id'], $input, $options);
            
            case 'document_conversion':
                return $this->processDocumentConversionJobWithStages($job['id'], $input, $options);
            
            case 'content_extraction':
                return $this->processContentExtractionJobWithStages($job['id'], $input, $options);
            
            default:
                throw new \Exception("Unsupported tool type: {$toolType}");
        }
    }

    /**
     * Process job based on tool type (legacy method)
     */
    private function processByToolType($job, $userId = null)
    {
        $toolType = $job['tool_type'];
        $input = $job['input'];
        $options = $job['options'];

        switch ($toolType) {
            case 'summarize':
                return $this->processSummarizeJob($input, $options, $userId);
            
            case 'math':
                return $this->processMathJob($input, $options);
            
            case 'flashcards':
                return $this->processFlashcardsJob($input, $options);
            
            case 'presentations':
                return $this->processPresentationsJob($input, $options);
            
            case 'document_chat':
                return $this->processDocumentChatJob($input, $options);
            
            default:
                throw new \Exception("Unsupported tool type: {$toolType}");
        }
    }

    /**
     * Process summarize job with detailed stage tracking
     */
    private function processSummarizeJobWithStages($jobId, $input, $options, $userId = null)
    {
        try {
            $contentType = $input['content_type'];
            $source = $input['source'];

            // Stage 1: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Analyzing content type: {$contentType}", 'info');

            switch ($contentType) {
                case 'text':
                    return $this->processTextSummarizationWithStages($jobId, $source['data'], $options);
                
                case 'link':
                    return $this->processLinkSummarizationWithStages($jobId, $source['data'], $options, $userId);
                
                case 'pdf':
                case 'image':
                case 'audio':
                case 'video':
                    return $this->processFileSummarizationWithStages($jobId, $source['data'], $options);
                
                default:
                    $this->failJob($jobId, "Unsupported content type: {$contentType}");
                    return ['success' => false, 'error' => "Unsupported content type: {$contentType}"];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process summarize job (legacy method)
     */
    private function processSummarizeJob($input, $options, $userId = null)
    {
        try {
            $contentType = $input['content_type'];
            $source = $input['source'];

            switch ($contentType) {
                case 'text':
                    return $this->processTextSummarization($source['data'], $options);
                
                case 'link':
                    return $this->processLinkSummarization($source['data'], $options, $userId);
                
                case 'pdf':
                case 'image':
                case 'audio':
                case 'video':
                    return $this->processFileSummarization($source['data'], $options);
                
                default:
                    return ['success' => false, 'error' => "Unsupported content type: {$contentType}"];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process text summarization
     */
    private function processTextSummarization($text, $options)
    {
        $result = $this->universalFileModule->getAIProcessingModule()->summarize($text, $options);
        
        return [
            'success' => true,
            'data' => $result,
            'metadata' => [
                'file_count' => 0,
                'tokens_used' => strlen($text) / 4,
                'confidence_score' => $result['confidence_score'] ?? 0.8
            ]
        ];
    }

    /**
     * Process link summarization (web or YouTube)
     */
    private function processLinkSummarization($url, $options, $userId = null)
    {
        // Check if it's a YouTube URL
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return $this->processYouTubeSummarization($url, $options, $userId);
        } else {
            return $this->processWebLinkSummarization($url, $options);
        }
    }

    /**
     * Process YouTube video summarization
     */
    private function processYouTubeSummarization($url, $options, $userId = null)
    {
        try {
            // Set timeout for YouTube processing
            set_time_limit(120); // 2 minutes timeout
            
            // Use UnifiedProcessingService for YouTube processing
            $unifiedService = app(\App\Services\Modules\UnifiedProcessingService::class);
            $result = $unifiedService->processYouTubeVideo($url, $options, $userId);
            
            return [
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                    'confidence_score' => $result['metadata']['confidence'] ?? 0.8,
                    'source_type' => 'youtube'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('YouTube processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'YouTube processing failed: ' . $e->getMessage(),
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => 0,
                    'confidence_score' => 0,
                    'source_type' => 'youtube'
                ]
            ];
        }
    }

    /**
     * Process web link summarization
     */
    private function processWebLinkSummarization($url, $options)
    {
        // Use WebScrapingService for web content
        $webScrapingService = app(\App\Services\WebScrapingService::class);
        $extractionResult = $webScrapingService->extractContent($url);
        
        if (!$extractionResult['success']) {
            return ['success' => false, 'error' => 'Failed to extract content from URL: ' . ($extractionResult['error'] ?? 'Unknown error')];
        }

        $content = $extractionResult['content'];
        if (empty($content)) {
            return ['success' => false, 'error' => 'No content found on the webpage'];
        }

        $result = $this->universalFileModule->getAIProcessingModule()->summarize($content, $options);
        
        return [
            'success' => true,
            'data' => $result,
            'metadata' => [
                'file_count' => 0,
                'tokens_used' => strlen($content) / 4,
                'confidence_score' => $result['confidence_score'] ?? 0.8,
                'source_type' => 'web'
            ]
        ];
    }

    /**
     * Process file summarization
     */
    private function processFileSummarization($fileId, $options)
    {
        $result = $this->universalFileModule->processFile($fileId, 'summarize', $options);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }

        return [
            'success' => true,
            'data' => $result['result'],
            'metadata' => [
                'file_count' => 1,
                'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                'confidence_score' => $result['metadata']['confidence'] ?? 0.8
            ]
        ];
    }

    /**
     * Process math job
     */
    private function processMathJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // Image-based math problem
                $result = $this->universalFileModule->processFile($input['file_id'], 'math', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'problem_type' => 'image',
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based math problem
                $problemData = [
                    'problem_text' => $input['text'],
                    'problem_type' => 'text',
                    'subject_area' => $options['subject_area'] ?? 'general',
                    'difficulty_level' => $options['difficulty_level'] ?? 'intermediate'
                ];
                $result = $this->universalFileModule->aiMathService->solveMathProblem($problemData, $input['user_id'] ?? 1);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'problem_type' => 'text',
                        'confidence_score' => 0.95
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process flashcards job
     */
    private function processFlashcardsJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // File-based flashcard generation
                $result = $this->universalFileModule->processFile($input['file_id'], 'flashcards', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'flashcard_count' => count($result['result']['flashcards'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based flashcard generation
                $count = $options['count'] ?? 5;
                $result = $this->universalFileModule->flashcardService->generateFlashcards($input['text'], $count, $options);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'flashcard_count' => count($result['flashcards'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process presentations job
     */
    private function processPresentationsJob($input, $options)
    {
        try {
            if (isset($input['file_id'])) {
                // File-based presentation generation
                $result = $this->universalFileModule->processFile($input['file_id'], 'presentations', $options);
                
                if (!$result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'slide_count' => count($result['result']['presentation_data']['slides'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            } else {
                // Text-based presentation generation
                // For text-based presentations, we don't need to create an AI result
                // Just process the outline generation directly
                
                $result = $this->universalFileModule->presentationService->generateOutline([
                    'text' => $input['text'],
                    'title' => $options['title'] ?? 'Generated Presentation',
                    'slides_count' => $options['slides_count'] ?? 5
                ], $input['user_id'] ?? 1);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'slide_count' => count($result['presentation']['slides'] ?? []),
                        'confidence_score' => 0.9
                    ]
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document chat job
     */
    private function processDocumentChatJob($input, $options)
    {
        try {
            // Document chat requires a file_id
            if (!isset($input['file_id'])) {
                return ['success' => false, 'error' => 'Document chat requires a file_id'];
            }
            
            // Document chat doesn't need file processing, just content extraction
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                return ['success' => false, 'error' => 'File not found'];
            }
            
            $result = $this->universalFileModule->extractContent($file, $options);
            
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error']];
            }

            return [
                'success' => true,
                'data' => [
                    'document_content' => $result['data']['text'],
                    'metadata' => $result['data']['metadata']
                ],
                'metadata' => [
                    'file_count' => 1,
                    'word_count' => str_word_count($result['data']['text']),
                    'confidence_score' => 0.95
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get user's jobs
     */
    public function getUserJobs($userId, $toolType = null, $status = null, $perPage = 15)
    {
        // This would typically query a database, but for now we'll use cache
        $jobs = [];
        $pattern = "universal_job_*";
        
        // In a real implementation, you'd store job IDs in a user index
        // For now, we'll return a mock response
        return [
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => count($jobs)
            ]
        ];
    }

    /**
     * Delete job
     */
    public function deleteJob($jobId)
    {
        return Cache::forget("universal_job_{$jobId}");
    }

    /**
     * Get job statistics
     */
    public function getJobStats($userId = null)
    {
        // This would typically query a database for real statistics
        return [
            'total_jobs' => 0,
            'completed_jobs' => 0,
            'failed_jobs' => 0,
            'running_jobs' => 0,
            'average_processing_time' => 0,
            'success_rate' => 0.0
        ];
    }

    /**
     * Process text summarization with stages
     */
    private function processTextSummarizationWithStages($jobId, $text, $options)
    {
        try {
            // Stage 2: Text Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_text',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Processing text content", 'info', [
                'text_length' => strlen($text),
                'word_count' => str_word_count($text)
            ]);

            // Stage 3: AI Processing
            $this->updateJob($jobId, [
                'stage' => 'ai_processing',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Sending text to AI Manager for summarization", 'info');

            $result = $this->universalFileModule->getAIProcessingModule()->summarize($text, $options);
            
            // Stage 4: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Summarization completed", 'info', [
                'summary_length' => strlen($result['insights'] ?? ''),
                'confidence_score' => $result['confidence_score'] ?? 0.8
            ]);
        
            return [
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => strlen($text) / 4,
                    'confidence_score' => $result['confidence_score'] ?? 0.8,
                    'processing_stages' => ['analyzing_content', 'processing_text', 'ai_processing', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "Text processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process link summarization with stages
     */
    private function processLinkSummarizationWithStages($jobId, $url, $options, $userId = null)
    {
        try {
            // Stage 2: URL Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_url',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing URL: {$url}", 'info');

            // Check if it's a YouTube URL
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                return $this->processYouTubeSummarizationWithStages($jobId, $url, $options, $userId);
            } else {
                return $this->processWebLinkSummarizationWithStages($jobId, $url, $options);
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Link processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process YouTube video summarization with stages
     */
    private function processYouTubeSummarizationWithStages($jobId, $url, $options, $userId = null)
    {
        try {
            // Stage 3: Video Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_video',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing YouTube video", 'info', ['url' => $url]);

            // Stage 4: Transcription
            $this->updateJob($jobId, [
                'stage' => 'transcribing',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Transcribing video content", 'info');

            // Use UnifiedProcessingService for YouTube processing
            $unifiedService = app(\App\Services\Modules\UnifiedProcessingService::class);
            $result = $unifiedService->processYouTubeVideo($url, $options, $userId);
            
            // Stage 5: AI Processing
            $this->updateJob($jobId, [
                'stage' => 'ai_processing',
                'progress' => 80
            ]);
            $this->addLog($jobId, "AI summarization completed", 'info', [
                'video_id' => $result['metadata']['video_id'] ?? 'unknown',
                'duration' => $result['metadata']['duration'] ?? 'unknown'
            ]);

            // Stage 6: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "YouTube processing completed", 'info');
            
            return [
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                    'confidence_score' => $result['metadata']['confidence'] ?? 0.8,
                    'source_type' => 'youtube',
                    'processing_stages' => ['analyzing_content', 'analyzing_url', 'processing_video', 'transcribing', 'ai_processing', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "YouTube processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'YouTube processing failed: ' . $e->getMessage(),
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => 0,
                    'confidence_score' => 0,
                    'source_type' => 'youtube'
                ]
            ];
        }
    }

    /**
     * Process web link summarization with stages
     */
    private function processWebLinkSummarizationWithStages($jobId, $url, $options)
    {
        try {
            // Stage 3: Web Scraping
            $this->updateJob($jobId, [
                'stage' => 'scraping_content',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Scraping web content from URL", 'info', ['url' => $url]);

            // Use WebScrapingService for web content
            $webScrapingService = app(\App\Services\WebScrapingService::class);
            $extractionResult = $webScrapingService->extractContent($url);
            
            if (!$extractionResult['success']) {
                $this->failJob($jobId, "Failed to extract content from URL: " . ($extractionResult['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => 'Failed to extract content from URL: ' . ($extractionResult['error'] ?? 'Unknown error')];
            }

            $content = $extractionResult['content'];
            if (empty($content)) {
                $this->failJob($jobId, "No content found on the webpage");
                return ['success' => false, 'error' => 'No content found on the webpage'];
            }

            // Stage 4: AI Processing
            $this->updateJob($jobId, [
                'stage' => 'ai_processing',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Processing scraped content with AI", 'info', [
                'content_length' => strlen($content),
                'word_count' => str_word_count($content)
            ]);

            $result = $this->universalFileModule->getAIProcessingModule()->summarize($content, $options);
            
            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Web content summarization completed", 'info');
        
            return [
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'file_count' => 0,
                    'tokens_used' => strlen($content) / 4,
                    'confidence_score' => $result['confidence_score'] ?? 0.8,
                    'source_type' => 'web',
                    'processing_stages' => ['analyzing_content', 'analyzing_url', 'scraping_content', 'ai_processing', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "Web link processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process file summarization with stages
     */
    private function processFileSummarizationWithStages($jobId, $fileId, $options)
    {
        try {
            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing uploaded file", 'info', ['file_id' => $fileId]);

            // Stage 3: Content Extraction
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 50
            ]);
            $this->addLog($jobId, "Extracting content from file", 'info');

            $result = $this->universalFileModule->processFile($fileId, 'summarize', $options);
            
            if (!$result['success']) {
                $this->failJob($jobId, "File processing failed: " . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }

            // Stage 4: AI Processing
            $this->updateJob($jobId, [
                'stage' => 'ai_processing',
                'progress' => 80
            ]);
            $this->addLog($jobId, "AI summarization completed", 'info', [
                'file_type' => $result['metadata']['file_type'] ?? 'unknown',
                'extracted_length' => strlen($result['result']['content'] ?? '')
            ]);

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 95
            ]);
            $this->addLog($jobId, "File processing completed", 'info');

            return [
                'success' => true,
                'data' => $result['result'],
                'metadata' => [
                    'file_count' => 1,
                    'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
                    'confidence_score' => $result['metadata']['confidence'] ?? 0.8,
                    'processing_stages' => ['analyzing_content', 'processing_file', 'extracting_content', 'ai_processing', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "File processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process math job with stages
     */
    private function processMathJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: Problem Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_problem',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing math problem", 'info');

            if (isset($input['file_id'])) {
                // Image-based math problem
                $this->updateJob($jobId, [
                    'stage' => 'processing_image',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Processing math problem image", 'info');

                $result = $this->universalFileModule->processFile($input['file_id'], 'math', $options);
                
                if (!$result['success']) {
                    $this->failJob($jobId, "Math processing failed: " . $result['error']);
                    return ['success' => false, 'error' => $result['error']];
                }

                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Math problem solved", 'info');

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'problem_type' => 'image',
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_problem', 'processing_image', 'finalizing']
                    ]
                ];
            } else {
                // Text-based math problem
                $this->updateJob($jobId, [
                    'stage' => 'solving_problem',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Solving text-based math problem", 'info');

                $problemData = [
                    'problem_text' => $input['text'],
                    'problem_type' => 'text',
                    'subject_area' => $options['subject_area'] ?? 'general',
                    'difficulty_level' => $options['difficulty_level'] ?? 'intermediate'
                ];
                $result = $this->universalFileModule->aiMathService->solveMathProblem($problemData, $input['user_id'] ?? 1);
                
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Math problem solved", 'info');
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'problem_type' => 'text',
                        'confidence_score' => 0.95,
                        'processing_stages' => ['analyzing_problem', 'solving_problem', 'finalizing']
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Math processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process flashcards job with stages
     */
    private function processFlashcardsJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing content for flashcard generation", 'info');

            if (isset($input['file_id'])) {
                // File-based flashcard generation
                $this->updateJob($jobId, [
                    'stage' => 'processing_file',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Processing file for flashcard generation", 'info');

                $result = $this->universalFileModule->processFile($input['file_id'], 'flashcards', $options);
                
                if (!$result['success']) {
                    $this->failJob($jobId, "Flashcard generation failed: " . $result['error']);
                    return ['success' => false, 'error' => $result['error']];
                }

                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Flashcards generated successfully", 'info', [
                    'flashcard_count' => count($result['result']['flashcards'] ?? [])
                ]);

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'flashcard_count' => count($result['result']['flashcards'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'processing_file', 'finalizing']
                    ]
                ];
            } else {
                // Text-based flashcard generation
                $this->updateJob($jobId, [
                    'stage' => 'generating_flashcards',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Generating flashcards from text", 'info');

                $count = $options['count'] ?? 5;
                $result = $this->universalFileModule->flashcardService->generateFlashcards($input['text'], $count, $options);
                
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Flashcards generated successfully", 'info', [
                    'flashcard_count' => count($result['flashcards'] ?? [])
                ]);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'flashcard_count' => count($result['flashcards'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'generating_flashcards', 'finalizing']
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Flashcard generation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process presentations job with stages
     */
    private function processPresentationsJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: Content Analysis
            $this->updateJob($jobId, [
                'stage' => 'analyzing_content',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Analyzing content for presentation generation", 'info');

            if (isset($input['file_id'])) {
                // File-based presentation generation
                $this->updateJob($jobId, [
                    'stage' => 'processing_file',
                    'progress' => 40
                ]);
                $this->addLog($jobId, "Processing file for presentation generation", 'info');

                $result = $this->universalFileModule->processFile($input['file_id'], 'presentations', $options);
                
                if (!$result['success']) {
                    $this->failJob($jobId, "Presentation generation failed: " . $result['error']);
                    return ['success' => false, 'error' => $result['error']];
                }

                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Presentation generated successfully", 'info', [
                    'slide_count' => count($result['result']['presentation_data']['slides'] ?? [])
                ]);

                return [
                    'success' => true,
                    'data' => $result['result'],
                    'metadata' => [
                        'file_count' => 1,
                        'slide_count' => count($result['result']['presentation_data']['slides'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'processing_file', 'finalizing']
                    ]
                ];
            } else {
                // Text-based presentation generation
                $this->updateJob($jobId, [
                    'stage' => 'generating_outline',
                    'progress' => 60
                ]);
                $this->addLog($jobId, "Generating presentation outline from text", 'info');

                $result = $this->universalFileModule->presentationService->generateOutline([
                    'text' => $input['text'],
                    'title' => $options['title'] ?? 'Generated Presentation',
                    'slides_count' => $options['slides_count'] ?? 5
                ], $input['user_id'] ?? 1);
                
                $this->updateJob($jobId, [
                    'stage' => 'finalizing',
                    'progress' => 90
                ]);
                $this->addLog($jobId, "Presentation outline generated successfully", 'info', [
                    'slide_count' => count($result['presentation']['slides'] ?? [])
                ]);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'metadata' => [
                        'file_count' => 0,
                        'slide_count' => count($result['presentation']['slides'] ?? []),
                        'confidence_score' => 0.9,
                        'processing_stages' => ['analyzing_content', 'generating_outline', 'finalizing']
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->failJob($jobId, "Presentation generation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document chat job with stages
     */
    private function processDocumentChatJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 2: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 20
            ]);
            $this->addLog($jobId, "Validating document file", 'info');

            // Document chat requires a file_id
            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Document chat requires a file_id");
                return ['success' => false, 'error' => 'Document chat requires a file_id'];
            }
            
            // Stage 3: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_document',
                'progress' => 40
            ]);
            $this->addLog($jobId, "Processing document for chat", 'info');

            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }
            
            // Stage 4: Content Extraction
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 70
            ]);
            $this->addLog($jobId, "Extracting content from document", 'info');

            $result = $this->universalFileModule->extractContent($file, $options);
            
            if (!$result['success']) {
                $this->failJob($jobId, "Content extraction failed: " . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Document processing completed", 'info', [
                'word_count' => str_word_count($result['data']['text'])
            ]);

            return [
                'success' => true,
                'data' => [
                    'document_content' => $result['data']['text'],
                    'metadata' => $result['data']['metadata']
                ],
                'metadata' => [
                    'file_count' => 1,
                    'word_count' => str_word_count($result['data']['text']),
                    'confidence_score' => 0.95,
                    'processing_stages' => ['validating_file', 'processing_document', 'extracting_content', 'finalizing']
                ]
            ];
        } catch (\Exception $e) {
            $this->failJob($jobId, "Document chat processing failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process document conversion job with stages
     */
    private function processDocumentConversionJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 1: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Validating file for conversion", 'info');

            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Document conversion requires a file_id");
                return ['success' => false, 'error' => 'Document conversion requires a file_id'];
            }

            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing file for conversion", 'info');

            // Get file from storage
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }

            // Stage 3: Document Conversion
            $this->updateJob($jobId, [
                'stage' => 'converting_document',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Converting document to {$input['target_format']}", 'info');

            // Use DocumentConverterService
            $documentConverterService = app(\App\Services\DocumentConverterService::class);
            
            // Use Storage facade to get the correct path (includes 'public' directory)
            $filePath = \Illuminate\Support\Facades\Storage::path($file->file_path);
            
            $this->addLog($jobId, "Using file path: {$filePath}", 'info');
            
            Log::info('Calling microservice with options', [
                'options' => $options,
                'options_type' => gettype($options)
            ]);
            
            $conversionResult = $documentConverterService->convertDocument(
                $filePath,
                $input['target_format'],
                $options
            );

            if (!$conversionResult || !isset($conversionResult['job_id'])) {
                $this->failJob($jobId, "Conversion failed to start");
                return ['success' => false, 'error' => 'Conversion failed to start'];
            }

            // Stage 4: Monitoring Conversion
            $this->updateJob($jobId, [
                'stage' => 'monitoring_conversion',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Monitoring conversion progress", 'info');

            // Poll for completion
            $maxAttempts = 60; // 5 minutes max
            $attempt = 0;
            
            $finalStatus = null;
            while ($attempt < $maxAttempts) {
                sleep(5); // Wait 5 seconds between checks
                $attempt++;
                
                $status = $documentConverterService->checkJobStatus($conversionResult['job_id']);
                
                if (isset($status['status']) && $status['status'] === 'completed') {
                    $finalStatus = $status; // Store the final status
                    break;
                } elseif (isset($status['status']) && $status['status'] === 'failed') {
                    $this->failJob($jobId, "Conversion failed: " . ($status['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Conversion failed'];
                }
            }

            if ($attempt >= $maxAttempts) {
                $this->failJob($jobId, "Conversion timeout");
                return ['success' => false, 'error' => 'Conversion timeout'];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Finalizing conversion result", 'info');

            // Use the final status response (since result endpoint returns null)
            $result = $finalStatus;
            
            if (!$result || !isset($result['status']) || $result['status'] !== 'completed') {
                $this->failJob($jobId, "Failed to get conversion result");
                return ['success' => false, 'error' => 'Failed to get conversion result'];
            }

            // Store converted file - handle both file_paths and output_files
            $filePath = $result['file_paths'][0] ?? $result['output_files'][0] ?? null;
            if (!$filePath) {
                $this->failJob($jobId, "No output file found in conversion result");
                return ['success' => false, 'error' => 'No output file found'];
            }
            
            $convertedFile = $documentConverterService->storeFile(
                $filePath,
                $input['original_filename'] . '.' . $input['target_format']
            );

            return [
                'success' => true,
                'data' => [
                    'conversion_result' => $result,
                    'converted_file' => $convertedFile,
                    'original_file' => [
                        'id' => $file->id,
                        'filename' => $file->original_filename,
                        'size' => $file->file_size
                    ]
                ],
                'metadata' => [
                    'file_count' => 1,
                    'conversion_format' => $input['target_format'],
                    'confidence_score' => 1.0,
                    'processing_stages' => ['validating_file', 'processing_file', 'converting_document', 'monitoring_conversion', 'finalizing']
                ]
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Document conversion failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process content extraction job with stages
     */
    private function processContentExtractionJobWithStages($jobId, $input, $options)
    {
        try {
            // Stage 1: File Validation
            $this->updateJob($jobId, [
                'stage' => 'validating_file',
                'progress' => 10
            ]);
            $this->addLog($jobId, "Validating file for content extraction", 'info');

            if (!isset($input['file_id'])) {
                $this->failJob($jobId, "Content extraction requires a file_id");
                return ['success' => false, 'error' => 'Content extraction requires a file_id'];
            }

            // Stage 2: File Processing
            $this->updateJob($jobId, [
                'stage' => 'processing_file',
                'progress' => 30
            ]);
            $this->addLog($jobId, "Processing file for content extraction", 'info');

            // Get file from storage
            $file = \App\Models\FileUpload::find($input['file_id']);
            if (!$file) {
                $this->failJob($jobId, "File not found");
                return ['success' => false, 'error' => 'File not found'];
            }

            // Stage 3: Content Extraction
            $this->updateJob($jobId, [
                'stage' => 'extracting_content',
                'progress' => 60
            ]);
            $this->addLog($jobId, "Extracting content from document", 'info');

            // Use DocumentConverterService
            $documentConverterService = app(\App\Services\DocumentConverterService::class);
            
            // Use Storage facade to get the correct path (includes 'public' directory)
            $filePath = \Illuminate\Support\Facades\Storage::path($file->file_path);
            
            $this->addLog($jobId, "Using file path: {$filePath}", 'info');
            
            $extractionResult = $documentConverterService->extractContent(
                $filePath,
                $input['extraction_type'],
                $input['language'],
                $options
            );

            if (!$extractionResult || !isset($extractionResult['job_id'])) {
                $this->failJob($jobId, "Content extraction failed to start");
                return ['success' => false, 'error' => 'Content extraction failed to start'];
            }

            // Stage 4: Monitoring Extraction
            $this->updateJob($jobId, [
                'stage' => 'monitoring_extraction',
                'progress' => 80
            ]);
            $this->addLog($jobId, "Monitoring content extraction progress", 'info');

            // Poll for completion
            $maxAttempts = 60; // 5 minutes max
            $attempt = 0;
            
            $finalStatus = null;
            while ($attempt < $maxAttempts) {
                sleep(5); // Wait 5 seconds between checks
                $attempt++;
                
                $status = $documentConverterService->checkJobStatus($extractionResult['job_id']);
                
                if (isset($status['status']) && $status['status'] === 'completed') {
                    $finalStatus = $status; // Store the final status
                    break;
                } elseif (isset($status['status']) && $status['status'] === 'failed') {
                    $this->failJob($jobId, "Content extraction failed: " . ($status['error'] ?? 'Unknown error'));
                    return ['success' => false, 'error' => 'Content extraction failed'];
                }
            }

            if ($attempt >= $maxAttempts) {
                $this->failJob($jobId, "Content extraction timeout");
                return ['success' => false, 'error' => 'Content extraction timeout'];
            }

            // Stage 5: Finalizing
            $this->updateJob($jobId, [
                'stage' => 'finalizing',
                'progress' => 90
            ]);
            $this->addLog($jobId, "Finalizing content extraction result", 'info');

            // Use the final status response (since result endpoint returns null)
            $result = $finalStatus;
            
            if (!$result || !isset($result['status']) || $result['status'] !== 'completed') {
                $this->failJob($jobId, "Failed to get extraction result");
                return ['success' => false, 'error' => 'Failed to get extraction result'];
            }

            return [
                'success' => true,
                'data' => [
                    'extraction_result' => $result,
                    'original_file' => [
                        'id' => $file->id,
                        'filename' => $file->original_filename,
                        'size' => $file->file_size
                    ],
                    'extracted_content' => $result['content'] ?? '',
                    'metadata' => $result['metadata'] ?? [],
                    'word_count' => $result['word_count'] ?? 0,
                    'page_count' => $result['page_count'] ?? 0,
                    'language_detected' => $result['language_detected'] ?? $input['language'],
                    'extraction_method' => $result['extraction_method'] ?? 'unknown'
                ],
                'metadata' => [
                    'file_count' => 1,
                    'extraction_type' => $input['extraction_type'],
                    'language' => $input['language'],
                    'confidence_score' => 0.9,
                    'processing_stages' => ['validating_file', 'processing_file', 'extracting_content', 'monitoring_extraction', 'finalizing']
                ]
            ];

        } catch (\Exception $e) {
            $this->failJob($jobId, "Content extraction failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate processing time
     */
    private function calculateProcessingTime($job)
    {
        $started = $job['metadata']['processing_started_at'] ?? null;
        $completed = $job['metadata']['processing_completed_at'] ?? now()->toISOString();
        
        if ($started) {
            $start = \Carbon\Carbon::parse($started);
            $end = \Carbon\Carbon::parse($completed);
            return $start->diffInSeconds($end);
        }
        
        return null;
    }
}
