<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeTranscriberService
{
    private $apiUrl;
    private $clientKey;
    private $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.youtube_transcriber.url');
        $this->clientKey = config('services.youtube_transcriber.client_key');
        $this->timeout = config('services.youtube_transcriber.timeout', 600); // 10 minutes for Smartproxy
        
        // Increase execution time for long-running transcriptions
        set_time_limit(600); // 10 minutes
    }

    /**
     * Transcribe YouTube video using the new Smartproxy endpoint (synchronous)
     */
    public function transcribe($videoUrl, $options = [])
    {
        // Try Smartproxy endpoint first (synchronous)
        $smartproxyResult = $this->transcribeWithSmartproxy($videoUrl, $options);
        if ($smartproxyResult['success']) {
            return $smartproxyResult;
        }
        
        // Fallback to original method (async job-based)
        return $this->transcribeOriginal($videoUrl, $options);
    }

    /**
     * Check if Smartproxy endpoint is available and working
     */
    public function isSmartproxyAvailable()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Client-Key' => $this->clientKey,
                ])
                ->get($this->apiUrl . '/scraper/smartproxy/subtitles', [
                    'url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw', // Test with short video
                    'format' => 'plain'
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("Smartproxy availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transcribe using the new Smartproxy endpoint
     */
    public function transcribeWithSmartproxy($videoUrl, $options = [])
    {
        $format = $options['format'] ?? 'bundle';
        $meta = $options['meta'] ?? false;

        try {
            Log::info("YouTube Transcriber Smartproxy API Request", [
                'video_url' => $videoUrl,
                'format' => $format
            ]);

            $queryParams = [
                'url' => $videoUrl,
                'format' => $format
            ];

            if ($meta) {
                $queryParams['include_meta'] = '1';
            }

            $response = Http::timeout(600) // 10 minutes for Smartproxy
                ->connectTimeout(30) // 30 seconds connection timeout
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Client-Key' => $this->clientKey,
                ])
                ->get($this->apiUrl . '/scraper/smartproxy/subtitles', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle different formats
                $contentField = 'subtitle_text';
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $contentField = 'article_text';
                }
                
                $content = $data[$contentField] ?? '';
                
                Log::info("YouTube Transcriber Smartproxy API Response successful", [
                    'video_id' => $data['video_id'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'content_length' => strlen($content),
                    'proxy_source' => $response->header('X-Proxy-Source')
                ]);

                $result = [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $content,
                    'meta' => $data['meta'] ?? null
                ];
                
                // For bundle format, also include the article field
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $result['article'] = $data['article_text'];
                }
                
                // For bundle format, include json_items if available
                if ($format === 'bundle' && isset($data['json_items'])) {
                    $result['json'] = [
                        'segments' => $data['json_items']
                    ];
                }
                
                return $result;
            } else {
                Log::error("YouTube Transcriber Smartproxy API Response failed", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Smartproxy transcription failed: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error("YouTube Transcriber Smartproxy API Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Smartproxy transcription failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Original transcribe method as fallback
     */
    public function transcribeOriginal($videoUrl, $options = [])
    {
        $format = $options['format'] ?? config('services.youtube_transcriber.default_format', 'article');
        $language = $options['language'] ?? 'auto';
        $headings = $options['headings'] ?? true;
        $articleMode = $options['article_mode'] ?? 'clean';
        $meta = $options['meta'] ?? false;

        try {
            Log::info("YouTube Transcriber API Request", [
                'video_url' => $videoUrl,
                'format' => $format,
                'language' => $language
            ]);

            $queryParams = [
                'url' => $videoUrl,
                'format' => $format,
                'lang' => $language
            ];

            // Add format-specific parameters
            if ($format === 'article') {
                $queryParams['headings'] = $headings ? 'true' : 'false';
                $queryParams['article_mode'] = $articleMode;
            }

            if ($format === 'json' && $meta) {
                $queryParams['meta'] = 'true';
            }

            // Use the correct /transcribe/ytd endpoint
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])->get($this->apiUrl . '/transcribe/ytd', $queryParams);

            if ($response->status() === 202) {
                // V3 async job - need to poll
                $jobData = $response->json();
                Log::info("V3 Async job started", [
                    'job_key' => $jobData['job_key'] ?? 'unknown',
                    'video_url' => $videoUrl
                ]);
                return $this->handleV3AsyncJob($jobData, $videoUrl, $format);
            }

            if ($response->status() === 504) {
                // Gateway timeout - try to get status or retry
                Log::warning("YouTube Transcriber API timeout (504), attempting retry", [
                    'video_url' => $videoUrl,
                    'format' => $format
                ]);
                return $this->handleTimeoutRetry($videoUrl, $format, $queryParams);
            }

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle different formats
                $contentField = 'subtitle_text';
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $contentField = 'article_text';
                }
                
                $content = $data[$contentField] ?? '';
                
                Log::info("YouTube Transcriber API Success", [
                    'video_id' => $data['video_id'] ?? 'unknown',
                    'language' => $data['language'] ?? 'unknown',
                    'format' => $data['format'] ?? 'unknown',
                    'content_field' => $contentField,
                    'content_length' => strlen($content)
                ]);

                $result = [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $content,
                    'meta' => $data['meta'] ?? null
                ];
                
                // For bundle format, also include the article field
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $result['article'] = $data['article_text'];
                }
                
                return $result;
            } else {
                Log::error("YouTube Transcriber API failed", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'YouTube Transcriber service failed: ' . $response->status(),
                    'video_id' => null,
                    'subtitle_text' => null
                ];
            }

        } catch (\Exception $e) {
            Log::error("YouTube Transcriber API Error", [
                'error' => $e->getMessage(),
                'video_url' => $videoUrl,
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's a timeout error
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'Maximum execution time') !== false) {
                return [
                    'success' => false,
                    'error' => 'YouTube transcription is taking longer than expected. This is normal for longer videos. Please try again or contact support if the issue persists.',
                    'video_id' => null,
                    'subtitle_text' => null
                ];
            }

            return [
                'success' => false,
                'error' => 'YouTube Transcriber service error: ' . $e->getMessage(),
                'video_id' => null,
                'subtitle_text' => null
            ];
        }
    }

    /**
     * Handle V3 async job with proper polling
     */
    private function handleV3AsyncJob($jobData, $videoUrl, $format)
    {
        $jobKey = $jobData['job_key'] ?? null;
        
        if (!$jobKey) {
            return [
                'success' => false,
                'error' => 'No job key received from V3 async endpoint',
                'video_id' => null,
                'subtitle_text' => null
            ];
        }

        Log::info("Starting V3 async job polling", [
            'job_key' => $jobKey,
            'video_url' => $videoUrl
        ]);

        $maxPollingAttempts = 120; // 20 minutes max (10 second intervals)
        $pollingInterval = 10; // seconds

        for ($attempt = 1; $attempt <= $maxPollingAttempts; $attempt++) {
            sleep($pollingInterval);

            try {
                // Check job status
                $statusResponse = Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => $this->clientKey,
                    ])->get($this->apiUrl . '/status', [
                        'job_key' => $jobKey
                    ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    
                    Log::info("V3 Job status check", [
                        'job_key' => $jobKey,
                        'attempt' => $attempt,
                        'status' => $statusData['status'] ?? 'unknown',
                        'stage' => $statusData['stage'] ?? 'unknown',
                        'progress' => $statusData['progress'] ?? 0
                    ]);
                    
                    if ($statusData['status'] === 'completed') {
                        Log::info("V3 Job completed, fetching result", [
                            'job_key' => $jobKey,
                            'attempts' => $attempt
                        ]);

                        // Fetch the final result
                        return $this->fetchV3JobResult($jobKey, $videoUrl, $format);
                    } elseif ($statusData['status'] === 'failed') {
                        Log::error("V3 Job failed", [
                            'job_key' => $jobKey,
                            'stage' => $statusData['stage'] ?? 'unknown',
                            'logs' => $statusData['logs'] ?? []
                        ]);

                        return [
                            'success' => false,
                            'error' => 'V3 transcription job failed: ' . ($statusData['stage'] ?? 'unknown error'),
                            'video_id' => null,
                            'subtitle_text' => null
                        ];
                    } elseif ($statusData['status'] === 'aborted') {
                        Log::warning("V3 Job aborted", [
                            'job_key' => $jobKey
                        ]);

                        return [
                            'success' => false,
                            'error' => 'V3 transcription job was aborted',
                            'video_id' => null,
                            'subtitle_text' => null
                        ];
                    }

                    // Job still running, continue polling
                } else {
                    Log::warning("V3 Status check failed", [
                        'job_key' => $jobKey,
                        'status' => $statusResponse->status(),
                        'attempt' => $attempt
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning("V3 Status check error", [
                    'job_key' => $jobKey,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            }
        }

        Log::error("V3 Job polling timed out", [
            'job_key' => $jobKey,
            'max_attempts' => $maxPollingAttempts
        ]);

        return [
            'success' => false,
            'error' => 'V3 transcription job timed out after ' . ($maxPollingAttempts * $pollingInterval) . ' seconds',
            'video_id' => null,
            'subtitle_text' => null
        ];
    }

    /**
     * Fetch the final result from V3 job
     */
    private function fetchV3JobResult($jobKey, $videoUrl, $format)
    {
        try {
            $resultResponse = Http::timeout(60)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])->get($this->apiUrl . '/result', [
                    'job_key' => $jobKey
                ]);

            if ($resultResponse->successful()) {
                $data = $resultResponse->json();
                
                // Handle different formats
                $contentField = 'subtitle_text';
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $contentField = 'article_text';
                }
                
                $content = $data[$contentField] ?? '';
                
                Log::info("V3 Job result fetched successfully", [
                    'job_key' => $jobKey,
                    'video_id' => $data['video_id'] ?? 'unknown',
                    'language' => $data['language'] ?? 'unknown',
                    'format' => $data['format'] ?? 'unknown',
                    'content_field' => $contentField,
                    'content_length' => strlen($content)
                ]);

                $result = [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $content,
                    'meta' => $data['meta'] ?? null
                ];
                
                // For bundle format, also include the article field
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $result['article'] = $data['article_text'];
                }
                
                return $result;
            } else {
                Log::error("V3 Job result fetch failed", [
                    'job_key' => $jobKey,
                    'status' => $resultResponse->status(),
                    'body' => $resultResponse->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to fetch V3 job result: ' . $resultResponse->status(),
                    'video_id' => null,
                    'subtitle_text' => null
                ];
            }

        } catch (\Exception $e) {
            Log::error("V3 Job result fetch error", [
                'job_key' => $jobKey,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error fetching V3 job result: ' . $e->getMessage(),
                'video_id' => null,
                'subtitle_text' => null
            ];
        }
    }

    /**
     * Handle timeout retry logic
     */
    private function handleTimeoutRetry($videoUrl, $format, $queryParams)
    {
        Log::info("Attempting timeout retry for video transcription", [
            'video_url' => $videoUrl,
            'format' => $format
        ]);

        // First, try to extract video ID and check if there's a cached result
        $videoId = $this->extractVideoId($videoUrl);
        if ($videoId) {
            // Try to get status for this video
            $statusResult = $this->checkVideoStatus($videoId, $format);
            if ($statusResult['success']) {
                return $statusResult;
            }
        }

        // If no cached result, wait a bit and retry the original request
        sleep(5); // Wait 5 seconds before retry
        
        try {
            $retryResponse = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])->get($this->apiUrl . '/transcribe/ytd', $queryParams);

            if ($retryResponse->status() === 202) {
                return $this->handleLongRunningJob($retryResponse->json(), $videoUrl, $format);
            }

            if ($retryResponse->successful()) {
                $data = $retryResponse->json();
                return [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $data['subtitle_text'] ?? '',
                    'meta' => $data['meta'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'Retry failed with status: ' . $retryResponse->status(),
                'video_id' => null,
                'subtitle_text' => null
            ];

        } catch (\Exception $e) {
            Log::error("Timeout retry failed", [
                'error' => $e->getMessage(),
                'video_url' => $videoUrl
            ]);

            return [
                'success' => false,
                'error' => 'Timeout retry failed: ' . $e->getMessage(),
                'video_id' => null,
                'subtitle_text' => null
            ];
        }
    }

    /**
     * Check video status by video ID
     */
    private function checkVideoStatus($videoId, $format)
    {
        try {
            // Try different possible job keys
            $possibleJobKeys = [
                "ytd:{$videoId}:auto",
                "ytd:{$videoId}:en",
                "ytd:{$videoId}:{$format}"
            ];

            foreach ($possibleJobKeys as $jobKey) {
                $statusResponse = Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => $this->clientKey,
                    ])->get($this->apiUrl . '/status', [
                        'job_key' => $jobKey
                    ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    
                    if ($statusData['status'] === 'completed' && isset($statusData['result'])) {
                        Log::info("Found completed job in cache", [
                            'job_key' => $jobKey,
                            'video_id' => $videoId
                        ]);

                        // Try to get the actual result
                        return $this->getCompletedResult($videoId, $format);
                    }
                }
            }

            return [
                'success' => false,
                'error' => 'No completed job found in cache',
                'video_id' => null,
                'subtitle_text' => null
            ];

        } catch (\Exception $e) {
            Log::error("Status check failed", [
                'error' => $e->getMessage(),
                'video_id' => $videoId
            ]);

            return [
                'success' => false,
                'error' => 'Status check failed: ' . $e->getMessage(),
                'video_id' => null,
                'subtitle_text' => null
            ];
        }
    }

    /**
     * Get completed result from cache
     */
    private function getCompletedResult($videoId, $format)
    {
        try {
            $videoUrl = "https://www.youtube.com/watch?v={$videoId}";
            $queryParams = [
                'url' => $videoUrl,
                'format' => $format,
                'lang' => 'auto'
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])->get($this->apiUrl . '/transcribe/ytd', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $data['subtitle_text'] ?? '',
                    'meta' => $data['meta'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve completed result',
                'video_id' => null,
                'subtitle_text' => null
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error retrieving completed result: ' . $e->getMessage(),
                'video_id' => null,
                'subtitle_text' => null
            ];
        }
    }

    /**
     * Extract video ID from YouTube URL
     */
    private function extractVideoId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Handle long-running job with polling
     */
    private function handleLongRunningJob($jobData, $videoUrl, $format)
    {
        $jobKey = $jobData['job_key'] ?? null;
        
        if (!$jobKey) {
            return [
                'success' => false,
                'error' => 'No job key received for long-running transcription',
                'video_id' => null,
                'subtitle_text' => null
            ];
        }

        Log::info("Starting polling for long-running transcription", [
            'job_key' => $jobKey
        ]);

        $maxPollingAttempts = 60; // 10 minutes max (10 second intervals)
        $pollingInterval = 10; // seconds

        for ($attempt = 1; $attempt <= $maxPollingAttempts; $attempt++) {
            sleep($pollingInterval);

            try {
                $statusResponse = Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => $this->clientKey,
                    ])->get($this->apiUrl . '/status', [
                        'job_key' => $jobKey
                    ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    
                    if ($statusData['status'] === 'completed') {
                        Log::info("Long-running transcription completed", [
                            'job_key' => $jobKey,
                            'attempts' => $attempt
                        ]);

                        // Fetch the actual result
                        return $this->transcribe($videoUrl, ['format' => $format]);
                    } elseif ($statusData['status'] === 'failed') {
                        Log::error("Long-running transcription failed", [
                            'job_key' => $jobKey,
                            'stage' => $statusData['stage'] ?? 'unknown'
                        ]);

                        return [
                            'success' => false,
                            'error' => 'Transcription job failed: ' . ($statusData['stage'] ?? 'unknown error'),
                            'video_id' => null,
                            'subtitle_text' => null
                        ];
                    }

                    Log::info("Transcription still processing", [
                        'job_key' => $jobKey,
                        'attempt' => $attempt,
                        'status' => $statusData['status'] ?? 'unknown'
                    ]);
                } else {
                    Log::warning("Status check failed", [
                        'job_key' => $jobKey,
                        'status' => $statusResponse->status()
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning("Status check error", [
                    'job_key' => $jobKey,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::error("Long-running transcription timed out", [
            'job_key' => $jobKey,
            'max_attempts' => $maxPollingAttempts
        ]);

        return [
            'success' => false,
            'error' => 'Transcription job timed out after ' . ($maxPollingAttempts * $pollingInterval) . ' seconds',
            'video_id' => null,
            'subtitle_text' => null
        ];
    }

    /**
     * Check V3 transcriber service health
     */
    public function checkV3Health()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])->get($this->apiUrl . '/health');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mode' => $data['mode'] ?? 'unknown',
                    'asr_available' => $data['asr'] ?? false,
                    'proxy_available' => $data['proxy'] ?? false,
                    'status' => $response->status()
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => 'Health check failed'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported formats
     */
    public function getSupportedFormats()
    {
        return ['plain', 'json', 'srt', 'article'];
    }

    /**
     * Validate format
     */
    public function validateFormat($format)
    {
        return in_array($format, $this->getSupportedFormats());
    }
}
