<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SummarizeJobService;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\AIResultService;
use Illuminate\Support\Facades\Log;

class ProcessSummarizeJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summarize:process-job {jobId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a summarize job in the background';

    private $summarizeJobService;
    private $unifiedProcessingService;
    private $aiResultService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        SummarizeJobService $summarizeJobService,
        UnifiedProcessingService $unifiedProcessingService,
        AIResultService $aiResultService
    ) {
        parent::__construct();
        $this->summarizeJobService = $summarizeJobService;
        $this->unifiedProcessingService = $unifiedProcessingService;
        $this->aiResultService = $aiResultService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobId = $this->argument('jobId');
        
        Log::info("Processing summarize job in background", [
            'job_id' => $jobId
        ]);

        try {
            $job = $this->summarizeJobService->getJob($jobId);
            if (!$job) {
                Log::error("Job not found", ['job_id' => $jobId]);
                return 1;
            }

            $this->summarizeJobService->updateJob($jobId, [
                'status' => 'running',
                'stage' => 'processing',
                'progress' => 10
            ]);

            $this->summarizeJobService->addLog($jobId, 'Starting content processing');

            // Process based on content type
            $result = null;
            if ($job['content_type'] === 'link') {
                // Handle different source structures
                $url = null;
                if (is_string($job['source'])) {
                    $url = $job['source'];
                } elseif (is_array($job['source'])) {
                    $url = $job['source']['data'] ?? $job['source']['url'] ?? null;
                }
                
                if (!$url) {
                    throw new \Exception('URL not found in source data');
                }
                
                $result = $this->processLink($url, $job['options'], $job['user_id']);
            } elseif ($job['content_type'] === 'text') {
                $text = null;
                if (is_string($job['source'])) {
                    $text = $job['source'];
                } elseif (is_array($job['source'])) {
                    $text = $job['source']['text'] ?? $job['source']['data'] ?? null;
                }
                
                if (!$text) {
                    throw new \Exception('Text not found in source data');
                }
                
                $result = $this->processText($text, $job['options']);
            } elseif ($job['content_type'] === 'file') {
                $fileId = null;
                if (is_array($job['source'])) {
                    $fileId = $job['source']['file_id'] ?? $job['source']['id'] ?? null;
                }
                
                if (!$fileId) {
                    throw new \Exception('File ID not found in source data');
                }
                
                $result = $this->processFile($fileId, $job['options']);
            } else {
                throw new \Exception('Unsupported content type: ' . $job['content_type']);
            }

            if ($result && !isset($result['error'])) {
                $this->summarizeJobService->updateJob($jobId, [
                    'status' => 'running',
                    'stage' => 'summarizing',
                    'progress' => 50
                ]);

                $this->summarizeJobService->addLog($jobId, 'Content extracted, starting summarization');

                // Save result and complete job
                $aiResult = $this->aiResultService->saveResult(
                    $job['user_id'],
                    'summarize',
                    $result['source_info']['title'] ?? 'Summarized Content',
                    $result['source_info']['description'] ?? 'Content summarized via AI',
                    $result['source_info'],
                    ['summary' => $result['summary']],
                    $result['metadata']
                );

                $result['ai_result'] = $aiResult;

                $this->summarizeJobService->completeJob($jobId, $result);
                $this->summarizeJobService->addLog($jobId, 'Summarization completed successfully');

                Log::info("Summarize job completed successfully", [
                    'job_id' => $jobId,
                    'user_id' => $job['user_id']
                ]);

            } else {
                $error = $result['error'] ?? 'Unknown processing error';
                $this->summarizeJobService->failJob($jobId, $error);
                $this->summarizeJobService->addLog($jobId, 'Processing failed: ' . $error, 'error');
                
                Log::error("Summarize job failed", [
                    'job_id' => $jobId,
                    'error' => $error
                ]);
            }

        } catch (\Exception $e) {
            $this->summarizeJobService->failJob($jobId, $e->getMessage());
            $this->summarizeJobService->addLog($jobId, 'Job failed with exception: ' . $e->getMessage(), 'error');
            Log::error('Process summarize job error: ' . $e->getMessage(), [
                'job_id' => $jobId,
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Process link content
     */
    private function processLink($url, $options, $userId = null)
    {
        try {
            // Check if it's a YouTube URL and use YouTube Transcriber
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                return $this->processYouTubeVideo($url, $options, $userId);
            }
            
            // For non-YouTube URLs, use web scraping
            $webScrapingService = app(\App\Services\WebScrapingService::class);
            $scrapingResult = $webScrapingService->extractWebContent($url);
            
            if (!$scrapingResult['success']) {
                return [
                    'error' => $scrapingResult['error'],
                    'metadata' => [
                        'content_type' => 'link',
                        'processing_time' => '0.5s',
                        'tokens_used' => 0,
                        'confidence' => 0.0
                    ],
                    'source_info' => [
                        'url' => $url,
                        'title' => 'Failed to extract content',
                        'word_count' => 0
                    ]
                ];
            }

            $content = $scrapingResult['content'];
            $metadata = $scrapingResult['metadata'];
            
            // Truncate content if too long for OpenAI
            $maxTokens = 12000;
            $truncatedContent = $this->truncateTextForOpenAI($content, $maxTokens);
            
            // Generate summary using AI Manager
            $aiProcessingModule = app(\App\Services\Modules\AIProcessingModule::class);
            $result = $aiProcessingModule->summarize($truncatedContent, $options);
            $summary = $result['summary'];

            return [
                'summary' => $summary,
                'metadata' => [
                    'content_type' => 'link',
                    'processing_time' => '3.2s',
                    'tokens_used' => strlen($content) / 4, // Rough estimate
                    'confidence' => 0.95
                ],
                'source_info' => [
                    'url' => $url,
                    'title' => $metadata['title'] ?? 'Untitled',
                    'description' => $metadata['description'] ?? '',
                    'author' => $metadata['author'] ?? '',
                    'published_date' => $metadata['published_date'] ?? '',
                    'word_count' => str_word_count($content)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Web link processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Unable to process this webpage. Please try a different URL or check if the website is accessible.',
                'metadata' => [
                    'content_type' => 'link',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'url' => $url,
                    'title' => 'Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Process YouTube video using YouTube Transcriber
     */
    private function processYouTubeVideo($videoUrl, $options, $userId = null)
    {
        try {
            Log::info("Processing YouTube video: {$videoUrl}");
            
            // Use unified processing service for YouTube videos
            $result = $this->unifiedProcessingService->processYouTubeVideo($videoUrl, $options, $userId ?? auth()->id());
            
            if (!$result['success']) {
                return [
                    'error' => $result['error'],
                    'metadata' => [
                        'content_type' => 'youtube',
                        'processing_time' => '0.5s',
                        'tokens_used' => 0,
                        'confidence' => 0.0
                    ],
                    'source_info' => [
                        'url' => $videoUrl,
                        'title' => 'Failed to process YouTube video',
                        'word_count' => 0
                    ]
                ];
            }

            return [
                'summary' => $result['summary'],
                'metadata' => [
                    'content_type' => 'youtube',
                    'processing_time' => '5-10 minutes',
                    'tokens_used' => strlen($result['summary']) / 4,
                    'confidence' => 0.95
                ],
                'source_info' => [
                    'url' => $videoUrl,
                    'title' => $result['metadata']['title'] ?? 'YouTube Video',
                    'description' => 'Video content extracted via transcription',
                    'author' => $result['metadata']['channel'] ?? 'Unknown',
                    'published_date' => '',
                    'word_count' => $result['metadata']['total_words'] ?? 0
                ],
                'ai_result' => $result['ai_result']
            ];

        } catch (\Exception $e) {
            Log::error('YouTube video processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Unable to process this YouTube video. Please try a different video or check if the video is accessible.',
                'metadata' => [
                    'content_type' => 'youtube',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'url' => $videoUrl,
                    'title' => 'Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Process text content
     */
    private function processText($text, $options)
    {
        try {
            // Truncate text if too long
            $maxTokens = 12000;
            $truncatedText = $this->truncateTextForOpenAI($text, $maxTokens);
            
            // Generate summary using AI Manager
            $aiProcessingModule = app(\App\Services\Modules\AIProcessingModule::class);
            $result = $aiProcessingModule->summarize($truncatedText, $options);
            $summary = $result['summary'];

            return [
                'summary' => $summary,
                'metadata' => [
                    'content_type' => 'text',
                    'processing_time' => '2.1s',
                    'tokens_used' => strlen($text) / 4,
                    'confidence' => 0.95
                ],
                'source_info' => [
                    'title' => 'Text Content',
                    'description' => 'User-provided text content',
                    'word_count' => str_word_count($text)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Text processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Unable to process the text content. Please try again.',
                'metadata' => [
                    'content_type' => 'text',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'title' => 'Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Process file content
     */
    private function processFile($fileId, $options)
    {
        try {
            $fileUpload = \App\Models\FileUpload::find($fileId);
            
            if (!$fileUpload) {
                throw new \Exception('File not found');
            }

            $filePath = storage_path('app/' . $fileUpload->file_path);
            $fileType = strtolower($fileUpload->file_type);
            
            $content = '';
            switch ($fileType) {
                case 'pdf':
                    $enhancedPDFService = app(\App\Services\EnhancedPDFService::class);
                    $result = $enhancedPDFService->extractText($filePath);
                    $content = $result['text'] ?? '';
                    break;
                case 'doc':
                case 'docx':
                    $wordService = app(\App\Services\WordService::class);
                    $result = $wordService->extractText($filePath);
                    $content = $result['text'] ?? '';
                    break;
                default:
                    throw new \Exception("Unsupported file type: {$fileType}");
            }
            
            if (empty($content)) {
                throw new \Exception('No content extracted from file');
            }
            
            // Truncate content if too long
            $maxTokens = 12000;
            $truncatedContent = $this->truncateTextForOpenAI($content, $maxTokens);
            
            // Generate summary using AI Manager
            $aiProcessingModule = app(\App\Services\Modules\AIProcessingModule::class);
            $result = $aiProcessingModule->summarize($truncatedContent, $options);
            $summary = $result['summary'];

            return [
                'summary' => $summary,
                'metadata' => [
                    'content_type' => 'file',
                    'file_type' => $fileType,
                    'processing_time' => '4.2s',
                    'tokens_used' => strlen($content) / 4,
                    'confidence' => 0.95
                ],
                'source_info' => [
                    'title' => $fileUpload->original_name,
                    'description' => 'File content extracted and summarized',
                    'file_size' => $fileUpload->file_size,
                    'word_count' => str_word_count($content)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('File processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Unable to process the file. Please check if the file is valid and try again.',
                'metadata' => [
                    'content_type' => 'file',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'title' => 'Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Truncate text for OpenAI token limits
     */
    private function truncateTextForOpenAI($text, $maxTokens)
    {
        $maxChars = $maxTokens * 4; // Rough estimate: 4 chars per token
        
        if (strlen($text) <= $maxChars) {
            return $text;
        }
        
        return substr($text, 0, $maxChars) . '...';
    }
}
