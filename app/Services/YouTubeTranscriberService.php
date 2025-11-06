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
     * Transcribe YouTube video using the new BrightData endpoint (synchronous)
     */
    public function transcribe($videoUrl, $options = [])
    {
        // Use BrightData endpoint (synchronous)
        Log::info("YouTubeTranscriberService: Attempting BrightData transcription", [
            'video_url' => $videoUrl,
            'options' => $options
        ]);
        
        $brightDataResult = $this->transcribeWithBrightData($videoUrl, $options);
        if ($brightDataResult['success']) {
            Log::info("YouTubeTranscriberService: BrightData transcription succeeded");
            return $brightDataResult;
        }
        
        // Check if BrightData failed with a validation error (422) - don't fallback in this case
        $errorDetails = $brightDataResult['error_details'] ?? [];
        if (isset($errorDetails['status_code']) && $errorDetails['status_code'] === 422) {
            Log::error("YouTubeTranscriberService: BrightData returned 422 validation error, not falling back", [
                'brightdata_error' => $brightDataResult['error'] ?? 'Unknown error',
                'error_details' => $errorDetails,
                'video_url' => $videoUrl
            ]);
            // Return the BrightData error directly - don't try fallback for validation errors
            return $brightDataResult;
        }
        
        // Log why BrightData failed before falling back
        Log::warning("YouTubeTranscriberService: BrightData transcription failed, falling back to original method", [
            'brightdata_error' => $brightDataResult['error'] ?? 'Unknown error',
            'video_url' => $videoUrl
        ]);
        
        // Fallback to original method (async job-based)
        Log::info("YouTubeTranscriberService: Attempting fallback transcription method");
        return $this->transcribeOriginal($videoUrl, $options);
    }

    /**
     * Transcribe using the new BrightData endpoint
     */
    public function transcribeWithBrightData($videoUrl, $options = [])
    {
        $format = $options['format'] ?? 'bundle';
        $headings = $options['headings'] ?? 1;
        $maxParagraphSentences = $options['max_paragraph_sentences'] ?? 7;
        $includeMeta = $options['include_meta'] ?? 1;

        try {
            Log::info("YouTube Transcriber BrightData API Request", [
                'video_url' => $videoUrl,
                'format' => $format
            ]);

            $payload = [
                'input' => [
                    ['url' => $videoUrl]
                ]
            ];

            $queryParams = [
                'dataset_id' => 'gd_lk56epmy2i5g7lzu0k',
                'format' => $format,
                'headings' => $headings,
                'max_paragraph_sentences' => $maxParagraphSentences,
                'include_meta' => $includeMeta
            ];

            $fullUrl = $this->apiUrl . '/brightdata/scrape?' . http_build_query($queryParams);
            
            Log::info("YouTube Transcriber BrightData API Request Details", [
                'full_url' => $fullUrl,
                'method' => 'POST',
                'payload' => $payload,
                'query_params' => $queryParams,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Client-Key' => $this->clientKey,
                ],
                'api_url' => $this->apiUrl,
                'client_key' => $this->clientKey
            ]);

            $response = Http::timeout(600) // 10 minutes for BrightData
                ->connectTimeout(30) // 30 seconds connection timeout
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Client-Key' => $this->clientKey,
                ])
                ->post($fullUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log raw response for debugging
                Log::info("YouTube Transcriber BrightData API Raw Response", [
                    'video_url' => $videoUrl,
                    'format' => $format,
                    'response_keys' => array_keys($data),
                    'has_article_text' => isset($data['article_text']),
                    'has_subtitle_text' => isset($data['subtitle_text']),
                    'article_text_length' => isset($data['article_text']) ? strlen($data['article_text']) : 0,
                    'subtitle_text_length' => isset($data['subtitle_text']) ? strlen($data['subtitle_text'] ?? '') : 0
                ]);
                
                // Handle BrightData response format - check multiple possible fields
                $content = '';
                if (!empty($data['article_text'])) {
                    $content = $data['article_text'];
                } elseif (!empty($data['subtitle_text'])) {
                    $content = $data['subtitle_text'];
                } elseif (!empty($data['content'])) {
                    $content = $data['content'];
                } elseif (!empty($data['text'])) {
                    $content = $data['text'];
                }
                
                // Check if content is actually available
                if (empty(trim($content))) {
                    Log::warning("YouTube Transcriber BrightData API returned empty content", [
                        'video_url' => $videoUrl,
                        'video_id' => $data['video_id'] ?? null,
                        'format' => $format,
                        'response_keys' => array_keys($data),
                        'article_text_present' => isset($data['article_text']),
                        'article_text_type' => isset($data['article_text']) ? gettype($data['article_text']) : 'not_set',
                        'article_text_empty' => isset($data['article_text']) ? empty($data['article_text']) : 'not_set',
                        'response_sample' => json_encode(array_slice($data, 0, 3)) // First 3 keys for debugging
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'No transcript content available. The video may not have captions/transcripts enabled, or the transcriber service returned empty content.',
                        'video_id' => $data['video_id'] ?? null,
                        'error_details' => [
                            'error_type' => 'empty_transcript',
                            'possible_causes' => [
                                'Video does not have captions/transcripts enabled',
                                'Video captions are not available',
                                'Transcriber service returned empty content'
                            ],
                            'response_keys' => array_keys($data)
                        ]
                    ];
                }
                
                Log::info("YouTube Transcriber BrightData API Response successful", [
                    'video_id' => $data['video_id'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'content_length' => strlen($content)
                ]);

                $result = [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $content,
                    'article_text' => $content,
                    'meta' => $data['meta'] ?? null
                ];
                
                // Include json_items if available (for bundle format)
                if (isset($data['json_items'])) {
                    $result['json_items'] = $data['json_items'];
                    $result['json'] = [
                        'segments' => $data['json_items']
                    ];
                }
                
                // Include transcript_json if available (sometimes separate from json_items)
                if (isset($data['transcript_json'])) {
                    $result['transcript_json'] = $data['transcript_json'];
                    // If json_items wasn't set, use transcript_json
                    if (!isset($result['json_items'])) {
                        $result['json_items'] = $data['transcript_json'];
                        $result['json'] = [
                            'segments' => $data['transcript_json']
                        ];
                    }
                }
                
                // Log what bundle data is available
                Log::info("YouTube Transcriber BrightData bundle data check", [
                    'video_id' => $data['video_id'] ?? null,
                    'format' => $format,
                    'has_json_items' => isset($result['json_items']),
                    'has_transcript_json' => isset($result['transcript_json']),
                    'response_keys' => array_keys($data),
                    'json_items_count' => isset($result['json_items']) ? (is_array($result['json_items']) ? count($result['json_items']) : 'not_array') : 'not_set'
                ]);
                
                return $result;
            } else {
                // Parse error response if available
                $errorBody = $response->body();
                $errorData = null;
                try {
                    $errorData = $response->json();
                } catch (\Exception $e) {
                    // If JSON parsing fails, use raw body
                }
                
                Log::error("YouTube Transcriber BrightData API Response failed", [
                    'status' => $response->status(),
                    'status_text' => $response->status() === 422 ? 'Unprocessable Entity' : 'Unknown',
                    'response_body' => $errorBody,
                    'error_data' => $errorData,
                    'request_params' => [
                        'format' => $format,
                        'headings' => $headings,
                        'max_paragraph_sentences' => $maxParagraphSentences,
                        'include_meta' => $includeMeta,
                        'dataset_id' => 'gd_lk56epmy2i5g7lzu0k'
                    ],
                    'headers' => $response->headers()
                ]);
                
                // Provide more specific error message for 422
                if ($response->status() === 422) {
                    $errorMessage = 'Invalid request parameters. ';
                    if ($errorData && isset($errorData['message'])) {
                        $errorMessage .= $errorData['message'];
                    } elseif ($errorData && isset($errorData['error'])) {
                        $errorMessage .= $errorData['error'];
                    } else {
                        $errorMessage .= 'The API rejected the request format or parameters.';
                    }
                    
                    return [
                        'success' => false,
                        'error' => $errorMessage,
                        'error_details' => [
                            'error_type' => 'validation_error',
                            'status_code' => 422,
                            'api_response' => $errorData ?? $errorBody
                        ]
                    ];
                }
                
                // Handle error data properly (could be array or string)
                $errorMsg = 'Unknown error';
                if (is_array($errorData)) {
                    if (isset($errorData['error']) && is_array($errorData['error'])) {
                        $errorMsg = $errorData['error']['message'] ?? $errorData['error']['details'] ?? json_encode($errorData['error']);
                    } elseif (isset($errorData['message'])) {
                        $errorMsg = $errorData['message'];
                    } elseif (isset($errorData['error'])) {
                        $errorMsg = is_string($errorData['error']) ? $errorData['error'] : json_encode($errorData['error']);
                    } else {
                        $errorMsg = json_encode($errorData);
                    }
                } elseif (is_string($errorBody)) {
                    $errorMsg = $errorBody;
                }
                
                return [
                    'success' => false,
                    'error' => 'BrightData transcription failed: ' . $errorMsg,
                    'error_details' => [
                        'status_code' => $response->status(),
                        'api_response' => $errorData ?? $errorBody
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error("YouTube Transcriber BrightData API Exception", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'BrightData transcription failed: ' . $e->getMessage(),
                'error_details' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Check if BrightData endpoint is available and working
     */
    public function isBrightDataAvailable()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Client-Key' => $this->clientKey,
                ])
                ->get($this->apiUrl . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("BrightData availability check failed: " . $e->getMessage());
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
        return ['plain', 'json', 'srt', 'article', 'bundle'];
    }

    /**
     * Validate format
     */
    public function validateFormat($format)
    {
        return in_array($format, $this->getSupportedFormats());
    }

    /**
     * Transcribe audio/video file by uploading directly to transcriber service
     * 
     * @param string $filePath Full path to the audio/video file
     * @param array $options Transcription options
     *   - format: 'plain', 'json', 'srt', 'article', 'bundle' (default: 'bundle')
     *   - lang: Language code or 'auto' (default: 'auto')
     *   - include_meta: Include metadata (default: true for bundle format)
     * @return array Transcription result with job_key for async processing
     */
    public function transcribeFileUpload($filePath, $options = [])
    {
        $format = $options['format'] ?? 'bundle';
        $lang = $options['lang'] ?? $options['language'] ?? 'auto';
        $includeMeta = $options['include_meta'] ?? ($format === 'bundle' ? true : false);

        try {
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found: ' . $filePath
                ];
            }

            Log::info("TranscriberService: Starting file upload transcription", [
                'file_path' => $filePath,
                'format' => $format,
                'lang' => $lang,
                'include_meta' => $includeMeta
            ]);

            // Prepare multipart form data
            $multipart = [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath)
                ],
                [
                    'name' => 'format',
                    'contents' => $format
                ]
            ];

            if ($lang !== 'auto') {
                $multipart[] = [
                    'name' => 'lang',
                    'contents' => $lang
                ];
            }

            if ($includeMeta) {
                $multipart[] = [
                    'name' => 'include_meta',
                    'contents' => 'true'
                ];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])
                ->asMultipart()
                ->post($this->apiUrl . '/transcribe/upload', $multipart);

            if ($response->successful()) {
                $data = $response->json();
                
                // Check if response contains job_key (async) or direct result
                if (isset($data['job_key'])) {
                    Log::info("TranscriberService: File upload job created", [
                        'job_key' => $data['job_key'],
                        'file_path' => $filePath
                    ]);
                    
                    // Poll for completion
                    return $this->pollFileUploadJob($data['job_key'], $format);
                } elseif (isset($data['subtitle_text']) || isset($data['article_text'])) {
                    // Direct result (synchronous)
                    // For bundle format, prioritize article_text; for others, prioritize subtitle_text
                    if ($format === 'bundle') {
                        $content = $data['article_text'] ?? $data['subtitle_text'] ?? '';
                    } else {
                        $content = $data['subtitle_text'] ?? $data['article_text'] ?? '';
                    }
                    
                    Log::info("TranscriberService: File transcription completed synchronously", [
                        'file_path' => $filePath,
                        'format' => $format,
                        'content_length' => strlen($content),
                        'has_article_text' => isset($data['article_text']),
                        'has_subtitle_text' => isset($data['subtitle_text']),
                        'has_json_items' => isset($data['json_items']),
                        'has_transcript_json' => isset($data['transcript_json'])
                    ]);
                    
                    $result = [
                        'success' => true,
                        'video_id' => $data['video_id'] ?? null,
                        'language' => $data['language'] ?? $lang,
                        'format' => $data['format'] ?? $format,
                        'subtitle_text' => $content,
                        'article_text' => $data['article_text'] ?? $content,
                        'meta' => $data['meta'] ?? null
                    ];
                    
                    // Include json_items if available (for bundle format)
                    if (isset($data['json_items'])) {
                        $result['json_items'] = $data['json_items'];
                        $result['json'] = [
                            'segments' => $data['json_items']
                        ];
                    }
                    
                    // Also include transcript_json if available
                    if (isset($data['transcript_json'])) {
                        $result['transcript_json'] = $data['transcript_json'];
                        // If json_items wasn't set, use transcript_json
                        if (!isset($result['json_items'])) {
                            $result['json_items'] = $data['transcript_json'];
                            $result['json'] = [
                                'segments' => $data['transcript_json']
                            ];
                        }
                    }
                    
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'error' => 'Unexpected response format from transcriber service',
                        'response' => $data
                    ];
                }
            } else {
                $errorBody = $response->body();
                $errorData = null;
                try {
                    $errorData = $response->json();
                } catch (\Exception $e) {
                    // If JSON parsing fails, use raw body
                }

                Log::error("TranscriberService: File upload failed", [
                    'status' => $response->status(),
                    'response' => $errorData ?? $errorBody,
                    'file_path' => $filePath
                ]);

                $errorMessage = 'File transcription failed';
                if ($errorData && isset($errorData['message'])) {
                    $errorMessage .= ': ' . $errorData['message'];
                } elseif ($errorData && isset($errorData['error'])) {
                    $errorMessage .= ': ' . $errorData['error'];
                } else {
                    $errorMessage .= ': ' . $errorBody;
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_details' => [
                        'status_code' => $response->status(),
                        'api_response' => $errorData ?? $errorBody
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error("TranscriberService: File upload exception", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'file_path' => $filePath
            ]);

            return [
                'success' => false,
                'error' => 'File transcription failed: ' . $e->getMessage(),
                'error_details' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Poll file upload job until completion
     * 
     * @param string $jobKey Job key from transcriber service
     * @param string $format Transcription format
     * @param int $maxAttempts Maximum polling attempts (default: 300 = 10 minutes)
     * @param int $interval Polling interval in seconds (default: 2)
     */
    private function pollFileUploadJob($jobKey, $format, $maxAttempts = 300, $interval = 2)
    {
        Log::info("TranscriberService: Starting job polling", [
            'job_key' => $jobKey,
            'max_attempts' => $maxAttempts,
            'interval' => $interval
        ]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            sleep($interval);

            try {
                $statusResponse = Http::timeout(30)
                    ->withHeaders([
                        'X-Client-Key' => $this->clientKey,
                    ])
                    ->get($this->apiUrl . '/status', [
                        'job_key' => $jobKey
                    ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    
                    Log::info("TranscriberService: Job status check", [
                        'job_key' => $jobKey,
                        'attempt' => $attempt,
                        'status' => $statusData['status'] ?? 'unknown',
                        'stage' => $statusData['stage'] ?? 'unknown',
                        'progress' => $statusData['progress'] ?? 0
                    ]);
                    
                    if ($statusData['status'] === 'completed') {
                        Log::info("TranscriberService: Job completed, fetching result", [
                            'job_key' => $jobKey,
                            'attempts' => $attempt
                        ]);

                        // Fetch the final result
                        return $this->fetchFileUploadResult($jobKey, $format);
                    } elseif ($statusData['status'] === 'failed') {
                        Log::error("TranscriberService: Job failed", [
                            'job_key' => $jobKey,
                            'stage' => $statusData['stage'] ?? 'unknown',
                            'logs' => $statusData['logs'] ?? []
                        ]);

                        return [
                            'success' => false,
                            'error' => 'Transcription job failed: ' . ($statusData['error'] ?? $statusData['stage'] ?? 'unknown error'),
                            'error_details' => [
                                'job_key' => $jobKey,
                                'stage' => $statusData['stage'] ?? null,
                                'logs' => $statusData['logs'] ?? []
                            ]
                        ];
                    }

                    // Job still running, continue polling
                } else {
                    Log::warning("TranscriberService: Status check failed", [
                        'job_key' => $jobKey,
                        'status' => $statusResponse->status(),
                        'attempt' => $attempt
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning("TranscriberService: Status check error", [
                    'job_key' => $jobKey,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            }
        }

        Log::error("TranscriberService: Job polling timed out", [
            'job_key' => $jobKey,
            'max_attempts' => $maxAttempts,
            'total_time_seconds' => $maxAttempts * $interval
        ]);

        return [
            'success' => false,
            'error' => 'Transcription job timed out after ' . ($maxAttempts * $interval) . ' seconds. The file may be too large or the service is overloaded.',
            'error_details' => [
                'job_key' => $jobKey,
                'max_attempts' => $maxAttempts,
                'interval' => $interval,
                'total_time_seconds' => $maxAttempts * $interval,
                'hint' => 'Large audio/video files can take longer to process. You can check the job status manually using: GET /status?job_key=' . $jobKey,
                'status_url' => $this->apiUrl . '/status?job_key=' . $jobKey
            ]
        ];
    }

    /**
     * Fetch the final result from file upload job
     */
    private function fetchFileUploadResult($jobKey, $format)
    {
        try {
            $resultResponse = Http::timeout(60)
                ->withHeaders([
                    'X-Client-Key' => $this->clientKey,
                ])
                ->get($this->apiUrl . '/result', [
                    'job_key' => $jobKey
                ]);

            if ($resultResponse->successful()) {
                $data = $resultResponse->json();
                
                // Handle different formats - bundle format returns article_text, not subtitle_text
                $contentField = 'subtitle_text';
                if ($format === 'bundle' && isset($data['article_text'])) {
                    $contentField = 'article_text';
                } elseif ($format !== 'bundle' && isset($data['subtitle_text'])) {
                    $contentField = 'subtitle_text';
                }
                
                // Extract content - prioritize article_text for bundle, subtitle_text for others
                $content = '';
                if ($format === 'bundle') {
                    $content = $data['article_text'] ?? $data['subtitle_text'] ?? '';
                } else {
                    $content = $data['subtitle_text'] ?? $data['article_text'] ?? '';
                }
                
                Log::info("TranscriberService: File upload result fetched successfully", [
                    'job_key' => $jobKey,
                    'format' => $data['format'] ?? $format,
                    'content_field' => $contentField,
                    'content_length' => strlen($content),
                    'has_article_text' => isset($data['article_text']),
                    'has_subtitle_text' => isset($data['subtitle_text']),
                    'has_json_items' => isset($data['json_items']),
                    'has_transcript_json' => isset($data['transcript_json'])
                ]);

                $result = [
                    'success' => true,
                    'video_id' => $data['video_id'] ?? null,
                    'language' => $data['language'] ?? null,
                    'format' => $data['format'] ?? $format,
                    'subtitle_text' => $content, // For compatibility, always include subtitle_text
                    'article_text' => $data['article_text'] ?? $content,
                    'meta' => $data['meta'] ?? null
                ];
                
                // Include json_items if available (for bundle format)
                if (isset($data['json_items'])) {
                    $result['json_items'] = $data['json_items'];
                    $result['json'] = [
                        'segments' => $data['json_items']
                    ];
                }
                
                // Also include transcript_json if available (sometimes duplicate of json_items)
                if (isset($data['transcript_json'])) {
                    $result['transcript_json'] = $data['transcript_json'];
                    // If json_items wasn't set, use transcript_json
                    if (!isset($result['json_items'])) {
                        $result['json_items'] = $data['transcript_json'];
                        $result['json'] = [
                            'segments' => $data['transcript_json']
                        ];
                    }
                }
                
                return $result;
            } else {
                Log::error("TranscriberService: Result fetch failed", [
                    'job_key' => $jobKey,
                    'status' => $resultResponse->status(),
                    'body' => $resultResponse->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to fetch transcription result: ' . $resultResponse->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error("TranscriberService: Result fetch error", [
                'job_key' => $jobKey,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error fetching transcription result: ' . $e->getMessage()
            ];
        }
    }
}
