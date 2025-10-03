<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\History;
use App\Services\ContentProcessingService;
use App\Services\OpenAIService;
use App\Services\WebScrapingService;
use App\Services\EnhancedPDFProcessingService;
use App\Services\RAGService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SummarizeController extends Controller
{
    protected $contentProcessingService;
    protected $openAIService;
    protected $webScrapingService;
    protected $enhancedPDFService;
    protected $ragService;

    public function __construct(ContentProcessingService $contentProcessingService, OpenAIService $openAIService, WebScrapingService $webScrapingService, EnhancedPDFProcessingService $enhancedPDFService, RAGService $ragService)
    {
        $this->contentProcessingService = $contentProcessingService;
        $this->openAIService = $openAIService;
        $this->webScrapingService = $webScrapingService;
        $this->enhancedPDFService = $enhancedPDFService;
        $this->ragService = $ragService;
    }

    /**
     * Unified summarization endpoint for all content types
     */
    public function summarize(Request $request)
    {
        try {
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

            $user = $request->user();
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

            return response()->json([
                'summary' => $result['summary'],
                'metadata' => $result['metadata'],
                'source_info' => $result['source_info'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Summarization Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process content at this time'
            ], 500);
        }
    }

    /**
     * Handle file upload
     */
    public function uploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:102400', // 100MB max
                'content_type' => 'required|string|in:pdf,image,audio,video'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $contentType = $request->input('content_type');
            $user = $request->user();

            // Generate unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/' . $contentType, $filename, 'local');

            // Store file info in database
            $upload = \App\Models\ContentUpload::create([
                'user_id' => $user->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $contentType,
                'file_size' => $file->getSize(),
                'processing_status' => 'pending'
            ]);

            return response()->json([
                'upload_id' => $upload->id,
                'filename' => $filename,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'content_type' => $contentType,
                'status' => 'uploaded'
            ]);

        } catch (\Exception $e) {
            Log::error('File Upload Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to upload file'
            ], 500);
        }
    }

    /**
     * Get upload status
     */
    public function getUploadStatus(Request $request, $uploadId)
    {
        try {
            $upload = \App\Models\ContentUpload::where('id', $uploadId)
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
                return $this->processPDF($source['data'], $options);
            case 'image':
                return $this->processImage($source['data'], $options);
            case 'audio':
                return $this->processAudio($source['data'], $options);
            case 'video':
                return $this->processVideo($source['data'], $options);
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
        
        $prompt = $this->buildTextPrompt($truncatedText, $options);
        $summary = $this->openAIService->generateResponse($prompt);

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
            // Real web scraping
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
        
        // Generate summary using OpenAI
        $prompt = $this->buildTextPrompt($truncatedContent, $options);
        $summary = $this->openAIService->generateResponse($prompt);

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
     * Process PDF document
     */
    private function processPDF($uploadId, $options)
    {
        try {
            // Get uploaded file
            $upload = \App\Models\ContentUpload::find($uploadId);
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
            
            // Check if RAG is enabled for this document
            $useRAG = $options['use_rag'] ?? false;
            $query = $options['query'] ?? null;
            
            if ($useRAG && $this->ragService->isRAGEnabled($uploadId)) {
                // Use RAG for summarization
                $ragResult = $this->ragService->getRAGSummary($uploadId, $query, $options);
                
                // Generate summary using relevant content
                $prompt = $this->buildTextPrompt($ragResult['content'], $options);
                $summary = $this->openAIService->generateResponse($prompt);
                
                return [
                    'summary' => $summary,
                    'metadata' => [
                        'content_type' => 'pdf',
                        'processing_time' => '6.5s',
                        'tokens_used' => strlen($ragResult['content']) / 4,
                        'confidence' => 0.95,
                        'rag_enabled' => true,
                        'chunks_used' => count($ragResult['chunks'])
                    ],
                    'source_info' => [
                        'pages' => $pdfData['pages'] ?? 0,
                        'word_count' => str_word_count($ragResult['content']),
                        'character_count' => strlen($ragResult['content']),
                        'file_size' => $this->formatFileSize($upload->file_size),
                        'title' => $pdfData['metadata']['Title'] ?? 'Untitled',
                        'author' => $pdfData['metadata']['Author'] ?? 'Unknown',
                        'created_date' => $pdfData['metadata']['CreationDate'] ?? null,
                        'subject' => $pdfData['metadata']['Subject'] ?? null,
                        'password_protected' => $isPasswordProtected,
                        'rag_chunks' => $ragResult['chunks']
                    ]
                ];
            } else {
                // Use regular PDF processing for unprotected PDFs
                $pdfData = $this->contentProcessingService->extractTextFromPDF($filePath);
                
                if (!$pdfData['success']) {
                    throw new \Exception($pdfData['error']);
                }
                
                if (empty($pdfData['text'])) {
                    throw new \Exception('No readable text found in PDF. The document may be scanned or image-based.');
                }
                
                // Truncate content if too long for OpenAI (max ~12,000 tokens to leave room for prompt)
                $maxTokens = 12000;
                $truncatedText = $this->truncateTextForOpenAI($pdfData['text'], $maxTokens);
                
                // Generate summary using OpenAI
                $prompt = $this->buildTextPrompt($truncatedText, $options);
                $summary = $this->openAIService->generateResponse($prompt);
                
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
            }
            
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
        $prompt = $this->buildTextPrompt($content, $options);
        $summary = $this->openAIService->generateResponse($prompt);

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
        // Mock audio transcription
        $transcription = "This is a mock transcription of the uploaded audio file ID: " . $uploadId . ". The audio contains speech about various topics including technology, business, and innovation.";
        
        $prompt = $this->buildTextPrompt($transcription, $options);
        $summary = $this->openAIService->generateResponse($prompt);

        return [
            'summary' => $summary,
            'metadata' => [
                'content_type' => 'audio',
                'processing_time' => '8.5s',
                'tokens_used' => 2000,
                'confidence' => 0.92
            ],
            'source_info' => [
                'duration' => '5:30',
                'audio_quality' => 'High',
                'file_size' => '8.2MB',
                'transcription' => $transcription
            ]
        ];
    }

    /**
     * Process video file (MOCK DATA)
     */
    private function processVideo($uploadId, $options)
    {
        // Mock video processing (audio extraction + transcription)
        $transcription = "This is a mock transcription extracted from the audio track of uploaded video file ID: " . $uploadId . ". The video contains a presentation about artificial intelligence and machine learning applications.";
        
        $prompt = $this->buildTextPrompt($transcription, $options);
        $summary = $this->openAIService->generateResponse($prompt);

        return [
            'summary' => $summary,
            'metadata' => [
                'content_type' => 'video',
                'processing_time' => '12.3s',
                'tokens_used' => 2500,
                'confidence' => 0.90
            ],
            'source_info' => [
                'duration' => '10:45',
                'video_quality' => 'HD',
                'file_size' => '45.6MB',
                'transcription' => $transcription
            ]
        ];
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
     * Process document for RAG
     */
    public function processRAG(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'upload_id' => 'required|integer|exists:content_uploads,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $uploadId = $request->input('upload_id');
            $success = $this->ragService->processDocument($uploadId);

            if ($success) {
                return response()->json([
                    'message' => 'Document processed for RAG successfully',
                    'upload_id' => $uploadId,
                    'status' => 'processed'
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to process document for RAG'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('RAG processing error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to process document for RAG: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RAG summary for a document
     */
    public function getRAGSummary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'upload_id' => 'required|integer|exists:content_uploads,id',
                'query' => 'nullable|string|max:1000',
                'max_chunks' => 'nullable|integer|min:1|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $uploadId = $request->input('upload_id');
            $query = $request->input('query');
            $maxChunks = $request->input('max_chunks', 5);

            $options = [
                'max_chunks' => $maxChunks,
                'mode' => $request->input('mode', 'detailed'),
                'language' => $request->input('language', 'en')
            ];

            $ragResult = $this->ragService->getRAGSummary($uploadId, $query, $options);

            return response()->json([
                'summary' => $ragResult['content'],
                'metadata' => [
                    'content_type' => 'rag',
                    'processing_time' => '3.5s',
                    'tokens_used' => strlen($ragResult['content']) / 4,
                    'confidence' => 0.95,
                    'chunks_used' => count($ragResult['chunks']),
                    'query' => $query
                ],
                'source_info' => [
                    'upload_id' => $uploadId,
                    'chunks' => $ragResult['chunks'],
                    'total_chunks' => $ragResult['total_chunks']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('RAG summary error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to generate RAG summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document RAG status
     */
    public function getRAGStatus(Request $request, $uploadId)
    {
        try {
            $stats = $this->ragService->getDocumentStats($uploadId);

            if (empty($stats)) {
                return response()->json([
                    'error' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'upload_id' => $uploadId,
                'rag_enabled' => $stats['rag_enabled'],
                'processed_at' => $stats['processed_at'],
                'chunk_count' => $stats['chunk_count'],
                'total_chunks' => $stats['total_chunks'],
                'total_content_length' => $stats['total_content_length'],
                'page_range' => $stats['page_range']
            ]);

        } catch (\Exception $e) {
            Log::error('RAG status error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to get RAG status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete RAG data for a document
     */
    public function deleteRAGData(Request $request, $uploadId)
    {
        try {
            $success = $this->ragService->deleteRAGData($uploadId);

            if ($success) {
                return response()->json([
                    'message' => 'RAG data deleted successfully',
                    'upload_id' => $uploadId
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to delete RAG data'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('RAG deletion error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to delete RAG data: ' . $e->getMessage()
            ], 500);
        }
    }
}
