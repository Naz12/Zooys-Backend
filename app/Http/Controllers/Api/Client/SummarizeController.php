<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\History;
use App\Services\Modules\UnifiedProcessingService;
use App\Services\Modules\AIProcessingModule;
use App\Services\Modules\UniversalFileManagementModule;
use App\Services\WebScrapingService;
use App\Services\AIResultService;
use App\Services\UniversalJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SummarizeController extends Controller
{
    protected $unifiedProcessingService;
    protected $aiProcessingModule;
    protected $universalFileModule;
    protected $webScrapingService;
    protected $aiResultService;
    protected $universalJobService;

    public function __construct(
        UnifiedProcessingService $unifiedProcessingService, 
        AIProcessingModule $aiProcessingModule,
        UniversalFileManagementModule $universalFileModule,
        WebScrapingService $webScrapingService,
        AIResultService $aiResultService,
        UniversalJobService $universalJobService
    ) {
        $this->unifiedProcessingService = $unifiedProcessingService;
        $this->aiProcessingModule = $aiProcessingModule;
        $this->universalFileModule = $universalFileModule;
        $this->webScrapingService = $webScrapingService;
        $this->aiResultService = $aiResultService;
        $this->universalJobService = $universalJobService;
    }

    /**
     * Unified summarization endpoint for all content types
     */
    public function summarize(Request $request)
    {
        try {
            // Increase execution time for long-running operations (especially YouTube)
            set_time_limit(600); // 10 minutes
            
            // Debug: Log the incoming request
            Log::info('Summarize Request:', [
                'content_type' => $request->input('content_type'),
                'source' => $request->input('source'),
                'options' => $request->input('options'),
                'all_data' => $request->all()
            ]);
            
            // Validate request based on content type
            $validator = $this->validateRequest($request);
            
            if ($validator->fails()) {
                Log::error('Validation failed:', $validator->errors()->toArray());
                Log::error('Request data:', $request->all());
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $contentType = $request->input('content_type');
            $source = $request->input('source');
            $options = $request->input('options', []);

            // Get or create tool
            $tool = Tool::firstOrCreate(
                ['slug' => 'summarize'],
                [
                    'name' => 'Content Summarizer',
                    'enabled' => true
                ]
            );

            // Process content based on type
            $result = $this->processContent($contentType, $source, $options);

            if (!$result) {
                return response()->json([
                    'error' => 'Unable to process content at this time'
                ], 500);
            }

            // Check if processing failed
            if (isset($result['error'])) {
                return response()->json([
                    'error' => $result['error'],
                    'metadata' => $result['metadata'],
                    'source_info' => $result['source_info']
                ], 400);
            }

            // Store in history
            $this->storeHistory($user, $tool, $source, $result);

            // Save to AIResult table for universal management
            $fileUploadId = null;
            if (isset($source['data']) && is_numeric($source['data'])) {
                // If source data is a file upload ID
                $fileUploadId = $source['data'];
            }

            $aiResult = $this->aiResultService->saveResult(
                $user->id,
                'summarize',
                $this->generateTitle($result['summary']),
                $this->generateDescription($result['summary']),
                $source,
                ['summary' => $result['summary']],
                array_merge($result['metadata'], [
                    'source_info' => $result['source_info'] ?? null
                ]),
                $fileUploadId
            );

            return response()->json([
                'success' => true,
                'message' => 'Content summarized successfully',
                'data' => [
                    'summary' => $result['summary'],
                    'metadata' => $result['metadata'],
                    'source_info' => $result['source_info'] ?? null,
                    'ai_result' => [
                        'id' => $aiResult['ai_result']->id,
                        'title' => $aiResult['ai_result']->title,
                        'file_url' => $aiResult['ai_result']->file_url,
                        'created_at' => $aiResult['ai_result']->created_at
                    ]
                ],
                'ui_helpers' => [
                    'summary_length' => strlen($result['summary']),
                    'word_count' => str_word_count($result['summary']),
                    'estimated_read_time' => ceil(str_word_count($result['summary']) / 200) . ' minutes',
                    'can_download' => true,
                    'can_share' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Summarization Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process content at this time'
            ], 500);
        }
    }

    /**
     * Validate file before upload (deprecated - use /api/files/upload instead)
     */

    /**
     * Validate file before upload
     */
    public function validateFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file',
                'content_type' => 'required|string|in:pdf,image,audio,video'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                    'message' => 'Please check your file and try again'
                ], 422);
            }

            $file = $request->file('file');
            $contentType = $request->input('content_type');
            
            // File validation
            $maxSize = 102400; // 100MB in KB
            $allowedTypes = [
                'pdf' => ['application/pdf'],
                'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
                'video' => ['video/mp4', 'video/avi', 'video/mov']
            ];

            $fileSize = $file->getSize() / 1024; // Convert to KB
            $mimeType = $file->getMimeType();
            
            $validation = [
                'is_valid' => true,
                'errors' => [],
                'warnings' => [],
                'file_info' => [
                    'name' => $file->getClientOriginalName(),
                    'size' => $fileSize,
                    'human_size' => $this->formatBytes($file->getSize()),
                    'type' => $mimeType,
                    'extension' => $file->getClientOriginalExtension()
                ]
            ];

            // Check file size
            if ($fileSize > $maxSize) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "File size ({$this->formatBytes($file->getSize())}) exceeds maximum allowed size (100MB)";
            }

            // Check file type
            if (!in_array($mimeType, $allowedTypes[$contentType] ?? [])) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "File type not supported for {$contentType} content";
            }

            // Warnings for large files
            if ($fileSize > 50000) { // 50MB
                $validation['warnings'][] = "Large file detected. Processing may take longer.";
            }

            return response()->json([
                'success' => true,
                'validation' => $validation,
                'can_upload' => $validation['is_valid'],
                'message' => $validation['is_valid'] ? 'File is valid and ready for upload' : 'File validation failed'
            ]);

        } catch (\Exception $e) {
            Log::error('File Validation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to validate file',
                'message' => 'An unexpected error occurred during validation.'
            ], 500);
        }
    }

    /**
     * Get upload status
     */
    public function getUploadStatus(Request $request, $uploadId)
    {
        try {
            $upload = \App\Models\FileUpload::where('id', $uploadId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$upload) {
                return response()->json([
                    'error' => 'Upload not found'
                ], 404);
            }

            return response()->json([
                'upload_id' => $upload->id,
                'status' => $upload->processing_status,
                'file_type' => $upload->file_type,
                'file_size' => $upload->file_size,
                'created_at' => $upload->created_at
            ]);

        } catch (\Exception $e) {
            Log::error('Upload Status Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to get upload status'
            ], 500);
        }
    }

    /**
     * Process document for chat
     */
    public function processDocument(Request $request, $uploadId)
    {
        try {
            $upload = \App\Models\FileUpload::where('id', $uploadId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$upload) {
                return response()->json([
                    'error' => 'Document not found'
                ], 404);
            }

            $filePath = Storage::path($upload->file_path);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'error' => 'Document file not found'
                ], 404);
            }

            // Process document based on type
            $result = $this->enhancedDocumentService->processDocument($uploadId, $filePath, $upload->file_type);

            return response()->json([
                'success' => true,
                'message' => 'Document processed successfully',
                'total_chunks' => $result['total_chunks'],
                'total_pages' => $result['total_pages']
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to process document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document processing status
     */
    public function getDocumentStatus(Request $request, $uploadId)
    {
        try {
            $upload = \App\Models\FileUpload::where('id', $uploadId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$upload) {
                return response()->json([
                    'error' => 'Document not found'
                ], 404);
            }

            $status = $this->vectorService->getDocumentStatus($uploadId);

            return response()->json([
                'document_id' => $uploadId,
                'status' => $status['status'],
                'total_chunks' => $status['total_chunks'],
                'total_pages' => $status['total_pages']
            ]);

        } catch (\Exception $e) {
            Log::error('Document status error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to get document status'
            ], 500);
        }
    }

    /**
     * Validate request based on content type
     */
    private function validateRequest(Request $request)
    {
        $contentType = $request->input('content_type');
        
        $rules = [
            'content_type' => 'required|string|in:pdf,image,audio,video,link,text',
            'source' => 'required|array',
            'source.type' => 'required|string|in:file,url,text',
            'options' => 'nullable|array'
        ];

        if ($contentType === 'text') {
            $rules['source.data'] = 'required|string|max:50000';
        } elseif ($contentType === 'link') {
            $rules['source.data'] = 'required|url';
        } else {
            $rules['source.data'] = 'required|string'; // upload_id for files
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Process content based on type
     */
    private function processContent($contentType, $source, $options)
    {
        switch ($contentType) {
            case 'text':
                return $this->processText($source['data'], $options);
            case 'link':
                return $this->processLink($source['data'], $options);
            case 'pdf':
            case 'image':
            case 'audio':
            case 'video':
                // Use universal file management module for all file types
                $result = $this->universalFileModule->processFile($source['data'], 'summarize', $options);
                return $result['success'] ? $result['result'] : ['error' => $result['error']];
            default:
                return null;
        }
    }

    /**
     * Process text content
     */
    private function processText($text, $options)
    {
        // Truncate content if too long for OpenAI
        $maxTokens = 12000;
        $truncatedText = $this->truncateTextForOpenAI($text, $maxTokens);
        
        $result = $this->aiProcessingModule->summarize($truncatedText, $options);
        $summary = $result['summary'];

        return [
            'summary' => $summary,
            'metadata' => [
                'content_type' => 'text',
                'processing_time' => '1.2s',
                'tokens_used' => 800,
                'confidence' => 0.95
            ],
            'source_info' => [
                'word_count' => str_word_count($text),
                'character_count' => strlen($text)
            ]
        ];
    }

    /**
     * Process web link
     */
    private function processLink($url, $options)
    {
        try {
            // Check if it's a YouTube URL and use YouTube Transcriber
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                return $this->processYouTubeVideo($url, $options);
            }
            
            // Real web scraping for non-YouTube URLs
            $scrapingResult = $this->webScrapingService->extractWebContent($url);
            
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
        $result = $this->aiProcessingModule->summarize($truncatedContent, $options);
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
    private function processYouTubeVideo($videoUrl, $options)
    {
        try {
            Log::info("Processing YouTube video: {$videoUrl}");
            
            // Use unified processing service for YouTube videos
            $result = $this->unifiedProcessingService->processYouTubeVideo($videoUrl, $options);
            
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
                'error' => 'Failed to process YouTube video: ' . $e->getMessage(),
                'metadata' => [
                    'content_type' => 'youtube',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'url' => $videoUrl,
                    'title' => 'Error processing YouTube video',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Process PDF document
     * @deprecated Use UniversalFileManagementModule instead
     */
    private function processPDF($uploadId, $options)
    {
        try {
            // Get uploaded file from the new FileUpload model
            $upload = \App\Models\FileUpload::find($uploadId);
            if (!$upload) {
                Log::error('PDF upload not found for ID: ' . $uploadId);
                throw new \Exception('PDF file not found. Please upload the file first.');
            }
            
            $filePath = Storage::path($upload->file_path);
            Log::info('PDF processing - Upload ID: ' . $uploadId . ', File path: ' . $filePath . ', File exists: ' . (file_exists($filePath) ? 'YES' : 'NO'));
            
            if (!file_exists($filePath)) {
                Log::error('PDF file not found on server: ' . $filePath);
                throw new \Exception('PDF file not found on server. Please re-upload the file.');
            }
            
            // Check if PDF is password-protected
            Log::info('Checking if PDF is password-protected...');
            $isPasswordProtected = $this->enhancedPDFService->isPasswordProtected($filePath);
            Log::info('PDF password protection check result: ' . ($isPasswordProtected ? 'PROTECTED' : 'NOT PROTECTED'));
            
            if ($isPasswordProtected) {
                Log::info('Password-protected PDF detected - cannot process');
                return [
                    'error' => 'This PDF is password-protected and cannot be summarized. Please use an unprotected PDF file.',
                    'metadata' => [
                        'content_type' => 'pdf',
                        'processing_time' => '0.5s',
                        'tokens_used' => 0,
                        'confidence' => 0.0
                    ],
                    'source_info' => [
                        'pages' => 0,
                        'word_count' => 0,
                        'file_size' => $this->formatFileSize($upload->file_size),
                        'password_protected' => true
                    ]
                ];
            }
            
            // Use regular PDF processing for unprotected PDFs
            $pdfData = $this->enhancedPDFService->extractTextFromPDF($filePath);
            
            if (!$pdfData['success']) {
                throw new \Exception($pdfData['error']);
            }
            
            if (empty($pdfData['text'])) {
                throw new \Exception('No readable text found in PDF. The document may be scanned or image-based.');
            }
            
            // Truncate content if too long for OpenAI (max ~12,000 tokens to leave room for prompt)
            $maxTokens = 12000;
            $truncatedText = $this->truncateTextForOpenAI($pdfData['text'], $maxTokens);
            
            // Generate summary using AI Manager
            $result = $this->aiProcessingModule->summarize($truncatedText, $options);
            $summary = $result['summary'];
            
            return [
                'summary' => $summary,
                'metadata' => [
                    'content_type' => 'pdf',
                    'processing_time' => '4.2s',
                    'tokens_used' => strlen($pdfData['text']) / 4, // Rough estimate
                    'confidence' => 0.95
                ],
                'source_info' => [
                    'pages' => $pdfData['pages'],
                    'word_count' => $pdfData['word_count'],
                    'character_count' => $pdfData['character_count'],
                    'file_size' => $this->formatFileSize($upload->file_size),
                    'title' => $pdfData['metadata']['Title'] ?? 'Untitled',
                    'author' => $pdfData['metadata']['Author'] ?? 'Unknown',
                    'created_date' => $pdfData['metadata']['CreationDate'] ?? null,
                    'subject' => $pdfData['metadata']['Subject'] ?? null,
                    'password_protected' => $isPasswordProtected
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Unable to process PDF: ' . $e->getMessage(),
                'metadata' => [
                    'content_type' => 'pdf',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'pages' => 0,
                    'word_count' => 0,
                    'file_size' => '0MB'
                ]
            ];
        }
    }

    /**
     * Process image
     */
    private function processImage($uploadId, $options)
    {
        // Mock image processing for now
        $content = "This is mock OCR text extracted from uploaded image ID: " . $uploadId;
        $result = $this->aiProcessingModule->summarize($content, $options);
        $summary = $result['summary'];

        return [
            'summary' => $summary,
            'metadata' => [
                'content_type' => 'image',
                'processing_time' => '4.2s',
                'tokens_used' => 1000,
                'confidence' => 0.85
            ],
            'source_info' => [
                'image_type' => 'PNG',
                'dimensions' => '1920x1080',
                'file_size' => '1.5MB'
            ]
        ];
    }

    /**
     * Process audio file (MOCK DATA)
     */
    private function processAudio($uploadId, $options)
    {
        try {
            // Get uploaded file
            $upload = \App\Models\FileUpload::find($uploadId);
            if (!$upload) {
                throw new \Exception('Audio file not found');
            }

            $filePath = storage_path('app/' . $upload->file_path);
            if (!file_exists($filePath)) {
                throw new \Exception('Audio file not found on disk');
            }

            // Use transcriber microservice for audio transcription
            $transcriberService = app(\App\Services\YouTubeTranscriberService::class);
            
            // Create a temporary URL for the file (transcriber expects URL)
            $fileUrl = url('storage/' . $upload->file_path);
            
            $transcriptionResult = $transcriberService->transcribe($fileUrl, [
                'format' => 'article',
                'language' => $options['language'] ?? 'auto'
            ]);

            if (!$transcriptionResult['success']) {
                throw new \Exception('Audio transcription failed: ' . $transcriptionResult['error']);
            }

            $transcription = $transcriptionResult['subtitle_text'] ?? '';
            
            // Generate summary using AI Manager
            $result = $this->aiProcessingModule->summarize($transcription, $options);
            $summary = $result['summary'];

            return [
                'summary' => $summary,
                'transcription' => $transcription,
                'metadata' => [
                    'content_type' => 'audio',
                    'processing_time' => '5-10 minutes',
                    'tokens_used' => strlen($transcription) / 4,
                    'confidence' => 0.95,
                    'transcriber_used' => true
                ],
                'source_info' => [
                    'title' => $upload->original_name,
                    'description' => 'Audio content transcribed and summarized',
                    'file_size' => $upload->file_size,
                    'word_count' => str_word_count($transcription)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Audio processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Failed to process audio file: ' . $e->getMessage(),
                'metadata' => [
                    'content_type' => 'audio',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'title' => 'Audio Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Process video file (MOCK DATA)
     */
    private function processVideo($uploadId, $options)
    {
        try {
            // Get uploaded file
            $upload = \App\Models\FileUpload::find($uploadId);
            if (!$upload) {
                throw new \Exception('Video file not found');
            }

            $filePath = storage_path('app/' . $upload->file_path);
            if (!file_exists($filePath)) {
                throw new \Exception('Video file not found on disk');
            }

            // Use transcriber microservice for video transcription
            $transcriberService = app(\App\Services\YouTubeTranscriberService::class);
            
            // Create a temporary URL for the file (transcriber expects URL)
            $fileUrl = url('storage/' . $upload->file_path);
            
            $transcriptionResult = $transcriberService->transcribe($fileUrl, [
                'format' => 'article',
                'language' => $options['language'] ?? 'auto'
            ]);

            if (!$transcriptionResult['success']) {
                throw new \Exception('Video transcription failed: ' . $transcriptionResult['error']);
            }

            $transcription = $transcriptionResult['subtitle_text'] ?? '';
            
            // Generate summary using AI Manager
            $result = $this->aiProcessingModule->summarize($transcription, $options);
            $summary = $result['summary'];

            return [
                'summary' => $summary,
                'transcription' => $transcription,
                'metadata' => [
                    'content_type' => 'video',
                    'processing_time' => '5-15 minutes',
                    'tokens_used' => strlen($transcription) / 4,
                    'confidence' => 0.95,
                    'transcriber_used' => true
                ],
                'source_info' => [
                    'title' => $upload->original_name,
                    'description' => 'Video content transcribed and summarized',
                    'file_size' => $upload->file_size,
                    'word_count' => str_word_count($transcription)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Video processing error: ' . $e->getMessage());
            
            return [
                'error' => 'Failed to process video file: ' . $e->getMessage(),
                'metadata' => [
                    'content_type' => 'video',
                    'processing_time' => '0.5s',
                    'tokens_used' => 0,
                    'confidence' => 0.0
                ],
                'source_info' => [
                    'title' => 'Video Processing Failed',
                    'word_count' => 0
                ]
            ];
        }
    }

    /**
     * Build prompt for text summarization
     */
    private function buildTextPrompt($text, $options)
    {
        $mode = $options['mode'] ?? 'detailed';
        $language = $options['language'] ?? 'en';
        $focus = $options['focus'] ?? 'summary';

        $prompt = "Please analyze and summarize the following content in {$language}:\n\n";
        $prompt .= "Content: {$text}\n\n";

        if ($mode === 'detailed') {
            $prompt .= "Provide a comprehensive summary including:\n";
            $prompt .= "1. Main topics and themes\n";
            $prompt .= "2. Key points and important details\n";
            $prompt .= "3. Target audience\n";
            $prompt .= "4. Educational value\n";
            $prompt .= "5. Overall assessment\n";
        } else {
            $prompt .= "Provide a brief summary focusing on the most important points.\n";
        }

        return $prompt;
    }

    /**
     * Store processing history
     */
    private function storeHistory($user, $tool, $source, $result)
    {
        try {
            History::create([
                'user_id' => $user->id,
                'tool_id' => $tool->id,
                'input' => json_encode($source),
                'output' => $result['summary'],
                'meta' => json_encode($result['metadata'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store history: ' . $e->getMessage());
        }
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . 'GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . 'KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Generate title from summary
     */
    private function generateTitle($summary)
    {
        $words = explode(' ', $summary);
        $title = implode(' ', array_slice($words, 0, 8));
        return strlen($title) > 60 ? substr($title, 0, 57) . '...' : $title;
    }

    /**
     * Generate description from summary
     */
    private function generateDescription($summary)
    {
        $words = explode(' ', $summary);
        $description = implode(' ', array_slice($words, 0, 20));
        return strlen($description) > 150 ? substr($description, 0, 147) . '...' : $description;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Truncate text to fit within OpenAI token limits
     */
    private function truncateTextForOpenAI($text, $maxTokens = 12000)
    {
        // Rough estimation: 1 token ≈ 4 characters
        $maxCharacters = $maxTokens * 4;
        
        if (strlen($text) <= $maxCharacters) {
            return $text;
        }
        
        // Truncate to max characters and add truncation notice
        $truncated = substr($text, 0, $maxCharacters);
        
        // Try to end at a sentence boundary
        $lastSentence = strrpos($truncated, '.');
        if ($lastSentence !== false && $lastSentence > $maxCharacters * 0.8) {
            $truncated = substr($truncated, 0, $lastSentence + 1);
        }
        
        return $truncated . "\n\n[Content truncated due to length - showing first " . number_format($maxTokens) . " tokens]";
    }

    /**
     * Start async summarization job
     */
    public function summarizeAsync(Request $request)
    {
        try {
            // Validate request
            $validator = $this->validateRequest($request);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $contentType = $request->input('content_type');
            $source = $request->input('source');
            $options = $request->input('options', []);

            // Create universal job
            $job = $this->universalJobService->createJob('summarize', [
                'content_type' => $contentType,
                'source' => $source
            ], $options, $user->id);

            // Queue background processing (non-blocking kickoff)
            \Illuminate\Support\Facades\Artisan::queue('universal:process-job', [
                'jobId' => $job['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Summarization job started',
                'job_id' => $job['id'],
                'status' => $job['status'],
                'poll_url' => url('/api/status?job_id=' . $job['id']),
                'result_url' => url('/api/result?job_id=' . $job['id'])
            ], 202);

        } catch (\Exception $e) {
            Log::error('Async summarize error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to start summarization job: ' . $e->getMessage()
            ], 500);
        }
    }

}
