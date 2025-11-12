<?php

namespace App\Services\Modules;

use App\Services\WebScrapingService;
use Illuminate\Support\Facades\Log;

class FlashcardModule
{
    private $aiProcessingModule;
    private $documentIntelligenceModule;
    private $transcriberModule;
    private $webScrapingService;
    private $universalFileModule;

    public function __construct(
        AIProcessingModule $aiProcessingModule,
        DocumentIntelligenceModule $documentIntelligenceModule,
        TranscriberModule $transcriberModule,
        WebScrapingService $webScrapingService,
        UniversalFileManagementModule $universalFileModule
    ) {
        $this->aiProcessingModule = $aiProcessingModule;
        $this->documentIntelligenceModule = $documentIntelligenceModule;
        $this->transcriberModule = $transcriberModule;
        $this->webScrapingService = $webScrapingService;
        $this->universalFileModule = $universalFileModule;
    }

    /**
     * Extract content from various input types
     */
    public function extractContent($input, $inputType, $options = [])
    {
        try {
            Log::info("FlashcardModule: Extracting content from {$inputType}", [
                'input_preview' => is_string($input) ? substr($input, 0, 100) : 'file',
                'options' => $options
            ]);

            switch ($inputType) {
                case 'text':
                    return $this->extractFromText($input, $options);

                case 'youtube':
                case 'video':
                    return $this->extractFromYouTube($input, $options);

                case 'url':
                case 'web':
                    return $this->extractFromUrl($input, $options);

                case 'file':
                    return $this->extractFromFile($input, $options);

                default:
                    throw new \Exception("Unsupported input type: {$inputType}");
            }
        } catch (\Exception $e) {
            Log::error('FlashcardModule: Content extraction error', [
                'error' => $e->getMessage(),
                'input_type' => $inputType
            ]);

            return [
                'success' => false,
                'content' => '',
                'metadata' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract content from plain text
     */
    private function extractFromText($text, $options)
    {
        if (empty(trim($text))) {
            throw new \Exception('Text input is empty');
        }

        return [
            'success' => true,
            'content' => trim($text),
            'metadata' => [
                'source_type' => 'text',
                'word_count' => str_word_count($text),
                'character_count' => strlen($text),
            ]
        ];
    }

    /**
     * Extract content from YouTube video using TranscriberModule
     */
    private function extractFromYouTube($videoUrl, $options)
    {
        Log::info("FlashcardModule: Transcribing YouTube video", [
            'video_url' => $videoUrl
        ]);

        $transcriptionResult = $this->transcriberModule->getArticleTranscript($videoUrl, true);

        if (!$transcriptionResult['success'] || empty($transcriptionResult['transcript'])) {
            throw new \Exception('Failed to transcribe YouTube video: ' . ($transcriptionResult['error'] ?? 'Unknown error'));
        }

        $transcript = $transcriptionResult['transcript'];

        return [
            'success' => true,
            'content' => $transcript,
            'metadata' => [
                'source_type' => 'youtube',
                'video_id' => $transcriptionResult['video_id'] ?? null,
                'word_count' => str_word_count($transcript),
                'character_count' => strlen($transcript),
            ]
        ];
    }

    /**
     * Extract content from URL/web page
     */
    private function extractFromUrl($url, $options)
    {
        Log::info("FlashcardModule: Extracting content from URL", [
            'url' => $url
        ]);

        $result = $this->webScrapingService->extractContent($url);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to extract content from URL');
        }

        return [
            'success' => true,
            'content' => $result['content'],
            'metadata' => array_merge($result['metadata'] ?? [], [
                'source_type' => 'url',
                'word_count' => str_word_count($result['content']),
                'character_count' => strlen($result['content']),
            ])
        ];
    }

    /**
     * Extract content from file using Document Intelligence Module
     */
    private function extractFromFile($fileId, $options)
    {
        Log::info("FlashcardModule: Extracting content from file", [
            'file_id' => $fileId
        ]);

        // Get file using Universal File Management Module
        $fileResult = $this->universalFileModule->getFile($fileId);

        if (!$fileResult['success']) {
            throw new \Exception('File not found: ' . ($fileResult['error'] ?? 'Unknown error'));
        }

        $file = $fileResult['file'];
        $filePath = $file->file_path ?? storage_path('app/' . $file->path);

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new \Exception('File path does not exist: ' . $filePath);
        }

        // Ingest document first (if not already ingested)
        $ingestOptions = array_merge([
            'ocr' => $options['ocr'] ?? 'auto',
            'lang' => $options['lang'] ?? 'eng',
            'force_fallback' => true
        ], $options);

        $ingestResult = $this->documentIntelligenceModule->ingestFromFileId($fileId, $ingestOptions);

        if (!$ingestResult['success']) {
            throw new \Exception('Failed to ingest document: ' . ($ingestResult['error'] ?? 'Unknown error'));
        }

        $docId = $ingestResult['doc_id'];

        // Poll for ingestion completion
        if (isset($ingestResult['job_id'])) {
            $pollResult = $this->documentIntelligenceModule->pollJobCompletion($ingestResult['job_id'], 60, 2);
            
            if (!$pollResult['success'] || ($pollResult['status'] ?? '') !== 'completed') {
                throw new \Exception('Document ingestion failed or timed out');
            }
        }

        // Extract content using Document Intelligence answer method
        $answerOptions = [
            'doc_ids' => [$docId],
            'llm_model' => $options['llm_model'] ?? 'llama3',
            'max_tokens' => $options['max_tokens'] ?? 2000, // More tokens for full content
            'top_k' => $options['top_k'] ?? 10, // More chunks for comprehensive content
            'temperature' => $options['temperature'] ?? 0.7,
            'force_fallback' => true
        ];

        Log::info("FlashcardModule: Requesting document content via Document Intelligence", [
            'doc_id' => $docId,
            'options' => $answerOptions
        ]);

        $answerResult = $this->documentIntelligenceModule->answer(
            "Please provide the complete content of this document. Include all text, key information, and important details. Format it as a comprehensive text that can be used for learning.",
            $answerOptions
        );

        if (!$answerResult['success']) {
            throw new \Exception('Failed to extract document content: ' . ($answerResult['error'] ?? 'Unknown error'));
        }

        $content = $answerResult['answer'] ?? '';

        if (empty($content)) {
            throw new \Exception('Document content extraction returned empty result');
        }

        return [
            'success' => true,
            'content' => $content,
            'metadata' => [
                'source_type' => 'file',
                'file_id' => $fileId,
                'doc_id' => $docId,
                'file_type' => $file->file_type ?? 'unknown',
                'word_count' => str_word_count($content),
                'character_count' => strlen($content),
            ]
        ];
    }

    /**
     * Detect input type automatically
     */
    public function detectInputType($input)
    {
        if (empty(trim($input))) {
            return 'text';
        }

        $input = trim($input);

        // Check if it's a YouTube URL
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $input)) {
            return 'youtube';
        }

        // Check if it's a URL (http:// or https://)
        if (preg_match('/^https?:\/\/.+/', $input)) {
            return 'url';
        }

        // Default to text
        return 'text';
    }

    /**
     * Validate input based on input type
     */
    public function validateInput($input, $inputType)
    {
        if (empty(trim($input))) {
            throw new \Exception('Input cannot be empty');
        }

        switch ($inputType) {
            case 'youtube':
            case 'video':
                if (!preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $input)) {
                    throw new \Exception('Invalid YouTube URL');
                }
                break;

            case 'url':
            case 'web':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new \Exception('Invalid URL format');
                }
                break;

            case 'text':
                if (strlen(trim($input)) < 3) {
                    throw new \Exception('Text input is too short (minimum 3 characters)');
                }
                break;

            case 'file':
                // File validation is handled by the file upload system
                break;

            default:
                throw new \Exception("Unsupported input type: {$inputType}");
        }

        return true;
    }

    /**
     * Generate flashcards from content using AIProcessingModule
     */
    public function generateFlashcards($content, $count = 5, $options = [])
    {
        try {
            // Validate content is not empty
            // Note: Content can be short (even a single topic/title) since AI generates flashcards ABOUT the topic
            if (empty(trim($content))) {
                throw new \Exception('Content cannot be empty. Please provide a topic, title, or description.');
            }
            
            // Minimum 2 characters (to allow single-word topics like "Python", "Java", etc.)
            if (strlen(trim($content)) < 2) {
                throw new \Exception('Content is too short. Please provide at least a topic or title.');
            }
            
            $wordCount = str_word_count($content);
            Log::info("FlashcardModule: Content validation", [
                'content_length' => strlen($content),
                'word_count' => $wordCount,
                'requested_count' => $count,
                'note' => 'Content treated as topic - AI will generate flashcards about this topic'
            ]);

            Log::info("FlashcardModule: Generating flashcards", [
                'content_length' => strlen($content),
                'word_count' => str_word_count($content),
                'count' => $count,
                'options' => $options
            ]);

            // Prepare options for flashcard generation
            $difficulty = $options['difficulty'] ?? 'intermediate';
            $style = $options['style'] ?? 'mixed';
            
            // Pass raw content and options to AIProcessingModule
            // It will dynamically construct the prompt from user input
            $aiOptions = array_merge([
                'model' => $options['model'] ?? config('services.ai_manager.default_model', 'deepseek-chat'),
                'count' => $count,
                'difficulty' => $difficulty,
                'style' => $style,
                'input_type' => $options['input_type'] ?? 'text'
            ], $options);

            // Use the generateFlashcards method - it will build the prompt dynamically
            Log::info("FlashcardModule: Calling AIProcessingModule::generateFlashcards", [
                'content_preview' => substr($content, 0, 100),
                'ai_options' => $aiOptions
            ]);
            
            try {
                $result = $this->aiProcessingModule->generateFlashcards($content, $aiOptions);
                Log::info("FlashcardModule: AIProcessingModule returned result", [
                    'has_result' => !empty($result),
                    'result_keys' => is_array($result) ? array_keys($result) : []
                ]);
            } catch (\Exception $e) {
                Log::error("FlashcardModule: AIProcessingModule exception", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Parse flashcards from result
            Log::info("FlashcardModule: About to parse flashcards", [
                'result_structure' => is_array($result) ? array_keys($result) : gettype($result),
                'has_data' => isset($result['data']),
                'has_raw_output' => isset($result['data']['raw_output']),
                'has_flashcards' => isset($result['flashcards']),
                'full_result_preview' => is_array($result) ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string)$result
            ]);
            
            try {
                $flashcards = $this->parseFlashcards($result);
            } catch (\Exception $e) {
                Log::error("FlashcardModule: parseFlashcards exception", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'result_structure' => is_array($result) ? array_keys($result) : gettype($result)
                ]);
                throw $e;
            }

            Log::info("FlashcardModule: After parsing flashcards", [
                'flashcards_count' => count($flashcards),
                'first_flashcard' => $flashcards[0] ?? null
            ]);

               if (empty($flashcards)) {
                   // Log full result structure for debugging
                   $resultPreview = is_array($result) ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$result;
                   
                   Log::error("FlashcardModule: Failed to parse flashcards - Empty result", [
                       'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
                       'result_type' => gettype($result),
                       'has_data' => isset($result['data']),
                       'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : null,
                       'has_data_content' => isset($result['data']['content']),
                       'has_data_raw_output' => isset($result['data']['raw_output']),
                       'data_content_type' => isset($result['data']['content']) ? gettype($result['data']['content']) : null,
                       'data_content_count' => isset($result['data']['content']) && is_array($result['data']['content']) ? count($result['data']['content']) : 0,
                       'data_raw_output_type' => isset($result['data']['raw_output']) ? gettype($result['data']['raw_output']) : null,
                       'data_raw_output_count' => isset($result['data']['raw_output']) && is_array($result['data']['raw_output']) ? count($result['data']['raw_output']) : 0,
                       'result_preview' => substr($resultPreview, 0, 2000), // Limit to 2000 chars
                       'parse_method' => 'parseFlashcards',
                       'requested_count' => $count ?? 'unknown'
                   ]);
                   
                   throw new \Exception('Failed to parse flashcards from AI response. Result structure: ' . (is_array($result) ? json_encode(array_keys($result)) : gettype($result)) . '. Check logs for full details. Requested count: ' . ($count ?? 'unknown'));
               }

            return [
                'success' => true,
                'flashcards' => $flashcards,
                'metadata' => [
                    'total_generated' => count($flashcards),
                    'requested_count' => $count,
                    'content_word_count' => str_word_count($content),
                    'generation_method' => 'ai_processing_module',
                    'model_used' => $result['model_used'] ?? 'unknown'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('FlashcardModule: Flashcard generation error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'flashcards' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse flashcards from AI response
     */
    private function parseFlashcards($result)
    {
        try {
            Log::info('FlashcardModule: Parsing flashcards from result', [
                'result_type' => gettype($result),
                'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
                'has_flashcards' => isset($result['flashcards']),
                'flashcards_count' => isset($result['flashcards']) ? (is_array($result['flashcards']) ? count($result['flashcards']) : 'not_array') : 0,
                'has_data' => isset($result['data']),
                'data_type' => isset($result['data']) ? gettype($result['data']) : null,
                'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : null,
                'has_data_raw_output' => isset($result['data']['raw_output']),
                'data_raw_output_type' => isset($result['data']['raw_output']) ? gettype($result['data']['raw_output']) : null,
                'has_data_raw_output_cards' => isset($result['data']['raw_output']['cards']),
                'data_raw_output_cards_type' => isset($result['data']['raw_output']['cards']) ? gettype($result['data']['raw_output']['cards']) : null,
                'data_raw_output_cards_count' => isset($result['data']['raw_output']['cards']) && is_array($result['data']['raw_output']['cards']) ? count($result['data']['raw_output']['cards']) : 0,
                'has_insights' => isset($result['insights']),
                'has_raw_output' => isset($result['raw_output']),
                'raw_output_keys' => isset($result['raw_output']) && is_array($result['raw_output']) ? array_keys($result['raw_output']) : null,
                'raw_output_cards' => isset($result['raw_output']['cards']) && is_array($result['raw_output']['cards']) ? count($result['raw_output']['cards']) : 0,
                'full_result_structure' => json_encode($result, JSON_PRETTY_PRINT)
            ]);

            // Try to get flashcards from result (multiple possible locations)
            // AI Manager returns flashcards in various formats:
            // 1. data.raw_output.cards (array with front/back)
            // 2. data.raw_output (direct array with question/answer or front/back)
            // 3. raw_output (direct array)
            // 4. flashcards (direct array)
            $flashcards = [];
            
            // Priority 1: Check data.raw_output.cards (from AIProcessingModule transformation - MOST COMMON FORMAT)
            Log::info('FlashcardModule: Checking data.raw_output.cards', [
                'has_data' => isset($result['data']),
                'has_raw_output' => isset($result['data']['raw_output']),
                'has_cards' => isset($result['data']['raw_output']['cards']),
                'cards_is_array' => isset($result['data']['raw_output']['cards']) && is_array($result['data']['raw_output']['cards']),
                'cards_count' => isset($result['data']['raw_output']['cards']) && is_array($result['data']['raw_output']['cards']) ? count($result['data']['raw_output']['cards']) : 0,
                'cards_empty' => isset($result['data']['raw_output']['cards']) && is_array($result['data']['raw_output']['cards']) ? empty($result['data']['raw_output']['cards']) : 'n/a'
            ]);
            
            if (isset($result['data']['raw_output']['cards']) && is_array($result['data']['raw_output']['cards']) && !empty($result['data']['raw_output']['cards'])) {
                $flashcards = $result['data']['raw_output']['cards'];
                Log::info('FlashcardModule: Found flashcards in data.raw_output.cards (Priority 1)', [
                    'count' => count($flashcards),
                    'first_card' => $flashcards[0] ?? null,
                    'first_card_type' => gettype($flashcards[0] ?? null),
                    'first_card_keys' => isset($flashcards[0]) && is_array($flashcards[0]) ? array_keys($flashcards[0]) : null,
                    'all_cards_structure' => json_encode($flashcards, JSON_PRETTY_PRINT)
                ]);
            }
            // Priority 2: Check if flashcards are directly in the result (from AIProcessingModule)
            elseif (isset($result['flashcards']) && is_array($result['flashcards']) && !empty($result['flashcards'])) {
                $flashcards = $result['flashcards'];
                Log::info('FlashcardModule: Found flashcards in result.flashcards (Priority 2)', [
                    'count' => count($flashcards)
                ]);
            }
            // Priority 3: Check if raw_output is itself an array of flashcards (AI Manager sometimes returns this)
            elseif (isset($result['raw_output']) && is_array($result['raw_output']) && !empty($result['raw_output'])) {
                // Check if it's a numeric array (indexed array, likely flashcards)
                $keys = array_keys($result['raw_output']);
                $isNumericArray = !empty($keys) && (is_numeric($keys[0]) || $keys[0] === 0 || $keys[0] === '0');
                
                if ($isNumericArray) {
                    $firstItem = $result['raw_output'][0] ?? null;
                    // Check if first item has flashcard-like structure
                    if (is_array($firstItem) && (isset($firstItem['question']) || isset($firstItem['front']) || isset($firstItem['answer']) || isset($firstItem['back']))) {
                        $flashcards = $result['raw_output'];
                        Log::info('FlashcardModule: Found flashcards in raw_output (direct array)', [
                            'count' => count($flashcards),
                            'first_item' => $firstItem
                        ]);
                    } else {
                        Log::warning('FlashcardModule: raw_output is numeric array but first item is not a flashcard', [
                            'first_item' => $firstItem,
                            'first_item_type' => gettype($firstItem)
                        ]);
                    }
                } else {
                    Log::warning('FlashcardModule: raw_output is not a numeric array', [
                        'keys' => $keys
                    ]);
                }
            }
            // Check raw_output.cards from AIProcessingModule (preserved structure)
            elseif (isset($result['raw_output']['cards']) && is_array($result['raw_output']['cards'])) {
                $flashcards = $result['raw_output']['cards'];
            }
            // Priority 4: Check data.raw_output (direct array - check if it's a numeric array of flashcards)
            elseif (isset($result['data']['raw_output']) && is_array($result['data']['raw_output']) && !empty($result['data']['raw_output'])) {
                // Check if it's a numeric array (indexed array, likely flashcards)
                $keys = array_keys($result['data']['raw_output']);
                $isNumericArray = !empty($keys) && (is_numeric($keys[0]) || $keys[0] === 0 || $keys[0] === '0');
                
                if ($isNumericArray) {
                    $firstItem = $result['data']['raw_output'][0] ?? null;
                    if (is_array($firstItem) && (isset($firstItem['question']) || isset($firstItem['front']) || isset($firstItem['answer']) || isset($firstItem['back']))) {
                        $flashcards = $result['data']['raw_output'];
                        Log::info('FlashcardModule: Found flashcards in data.raw_output (direct array - Priority 4)', [
                            'count' => count($flashcards),
                            'first_item' => $firstItem
                        ]);
                    }
                }
            }
            // Check data.raw_output as direct array (AI Manager array format: [...])
            elseif (isset($result['data']['raw_output']) && is_array($result['data']['raw_output']) && !empty($result['data']['raw_output'])) {
                // Check if it's a numeric array (indexed array, likely flashcards)
                $keys = array_keys($result['data']['raw_output']);
                $isNumericArray = !empty($keys) && (is_numeric($keys[0]) || $keys[0] === 0 || $keys[0] === '0');
                
                if ($isNumericArray) {
                    $firstItem = $result['data']['raw_output'][0] ?? null;
                    // If first item has question/answer or front/back, it's flashcards
                    if (is_array($firstItem) && (isset($firstItem['question']) || isset($firstItem['front']) || isset($firstItem['answer']) || isset($firstItem['back']))) {
                        $flashcards = $result['data']['raw_output'];
                        Log::info('FlashcardModule: Found flashcards in data.raw_output (direct array format)', [
                            'count' => count($flashcards),
                            'first_item' => $firstItem
                        ]);
                    }
                }
            }
            elseif (isset($result['data']['flashcards']) && is_array($result['data']['flashcards'])) {
                $flashcards = $result['data']['flashcards'];
            }
            elseif (isset($result['data']['raw_output']['flashcards']) && is_array($result['data']['raw_output']['flashcards'])) {
                $flashcards = $result['data']['raw_output']['flashcards'];
            }

            // If flashcards is already an array, validate and return
            if (is_array($flashcards) && !empty($flashcards)) {
                Log::info('FlashcardModule: Found flashcards array', [
                    'count' => count($flashcards),
                    'first_card' => $flashcards[0] ?? null
                ]);
                $validated = $this->validateFlashcards($flashcards);
                if (!empty($validated)) {
                    Log::info('FlashcardModule: Successfully parsed flashcards from array', [
                        'count' => count($validated)
                    ]);
                    return $validated;
                } else {
                    Log::warning('FlashcardModule: validateFlashcards returned empty array', [
                        'input_count' => count($flashcards),
                        'input_structure' => $flashcards[0] ?? null
                    ]);
                }
            } else {
                Log::warning('FlashcardModule: No flashcards array found', [
                    'is_array' => is_array($flashcards),
                    'empty' => empty($flashcards),
                    'type' => gettype($flashcards)
                ]);
            }

            // Try to parse from insights, generated_content, or data.raw_output
            // The AI Manager returns data in various nested structures
            $content = $result['insights'] ?? 
                      $result['generated_content'] ?? 
                      $result['data']['insights'] ??
                      $result['data']['generated_content'] ??
                      $result['data']['raw_output'] ?? 
                      (is_string($result['data']['raw_output'] ?? null) ? $result['data']['raw_output'] : '') ??
                      (isset($result['data']['raw_output']['data']) ? json_encode($result['data']['raw_output']['data']) : '') ??
                      '';

            if (empty($content)) {
                Log::warning('FlashcardModule: No content found in result', [
                    'result_structure' => $result
                ]);
                return [];
            }

            // Log the content for debugging
            Log::info('FlashcardModule: Attempting to parse content', [
                'content_length' => strlen($content),
                'content_preview' => substr($content, 0, 200)
            ]);

            // Try to extract JSON array from response
            $jsonStart = strpos($content, '[');
            $jsonEnd = strrpos($content, ']') + 1;

            if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart);
                $flashcards = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($flashcards)) {
                    $validated = $this->validateFlashcards($flashcards);
                    if (!empty($validated)) {
                        Log::info('FlashcardModule: Successfully parsed flashcards from JSON', [
                            'count' => count($validated)
                        ]);
                        return $validated;
                    }
                } else {
                    Log::warning('FlashcardModule: JSON decode failed', [
                        'json_error' => json_last_error_msg(),
                        'json_string_preview' => substr($jsonString, 0, 200)
                    ]);
                }
            }

            // Fallback: try to parse manually
            $manual = $this->parseFlashcardsManually($content);
            if (!empty($manual)) {
                Log::info('FlashcardModule: Successfully parsed flashcards manually', [
                    'count' => count($manual)
                ]);
                return $manual;
            }

            Log::error('FlashcardModule: All parsing methods failed', [
                'content_preview' => substr($content, 0, 500)
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('FlashcardModule: Flashcard parsing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Validate and clean flashcards
     * Handles both "question/answer" and "front/back" formats
     */
    private function validateFlashcards($flashcards)
    {
        $validFlashcards = [];

        Log::info('FlashcardModule: validateFlashcards input', [
            'input_count' => count($flashcards),
            'first_card' => $flashcards[0] ?? null,
            'first_card_type' => gettype($flashcards[0] ?? null),
            'first_card_keys' => isset($flashcards[0]) && is_array($flashcards[0]) ? array_keys($flashcards[0]) : null
        ]);

        foreach ($flashcards as $index => $card) {
            if (!is_array($card)) {
                Log::warning('FlashcardModule: validateFlashcards - Card is not array', [
                    'index' => $index,
                    'card_type' => gettype($card),
                    'card_value' => $card
                ]);
                continue;
            }
            
            // Handle "question" and "answer" format (from /api/process-text)
            if (isset($card['question']) && isset($card['answer'])) {
                $question = trim($card['question']);
                $answer = trim($card['answer']);
                
                if (!empty($question) && !empty($answer)) {
                    $validFlashcards[] = [
                        'front' => $question,  // Normalize to front/back
                        'back' => $answer,    // Normalize to front/back
                        'question' => $question,  // Keep original for compatibility
                        'answer' => $answer        // Keep original for compatibility
                    ];
                } else {
                    Log::warning('FlashcardModule: validateFlashcards - Empty question/answer', [
                        'index' => $index,
                        'question' => $question,
                        'answer' => $answer
                    ]);
                }
            }
            // Handle "front" and "back" format (from /api/custom-prompt)
            elseif (isset($card['front']) && isset($card['back'])) {
                $front = trim($card['front']);
                $back = trim($card['back']);
                
                if (!empty($front) && !empty($back)) {
                    $validFlashcards[] = [
                        'front' => $front,
                        'back' => $back,
                        'question' => $front,  // Add question/answer for compatibility
                        'answer' => $back
                    ];
                } else {
                    Log::warning('FlashcardModule: validateFlashcards - Empty front/back', [
                        'index' => $index,
                        'front' => $front,
                        'back' => $back
                    ]);
                }
            } else {
                Log::warning('FlashcardModule: validateFlashcards - Card missing required fields', [
                    'index' => $index,
                    'card_keys' => array_keys($card),
                    'has_question' => isset($card['question']),
                    'has_answer' => isset($card['answer']),
                    'has_front' => isset($card['front']),
                    'has_back' => isset($card['back']),
                    'card' => $card
                ]);
            }
        }
        
        Log::info('FlashcardModule: validateFlashcards result', [
            'input_count' => count($flashcards),
            'valid_count' => count($validFlashcards),
            'first_valid' => $validFlashcards[0] ?? null
        ]);

        return $validFlashcards;
    }

    /**
     * Manual parsing fallback
     * Handles various text formats
     */
    private function parseFlashcardsManually($response)
    {
        $flashcards = [];
        $lines = explode("\n", $response);

        $currentCard = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Handle "Question: ..." format
            if (preg_match('/^Question:\s*(.+)$/i', $line, $matches)) {
                $currentCard['question'] = trim($matches[1]);
            }
            // Handle "Answer: ..." format
            elseif (preg_match('/^Answer:\s*(.+)$/i', $line, $matches)) {
                $currentCard['answer'] = trim($matches[1]);

                if (!empty($currentCard['question']) && !empty($currentCard['answer'])) {
                    $flashcards[] = [
                        'question' => $currentCard['question'],
                        'answer' => $currentCard['answer']
                    ];
                    $currentCard = [];
                }
            }
            // Handle "Front: ..." format
            elseif (preg_match('/^Front:\s*(.+)$/i', $line, $matches)) {
                $currentCard['question'] = trim($matches[1]);
            }
            // Handle "Back: ..." format
            elseif (preg_match('/^Back:\s*(.+)$/i', $line, $matches)) {
                $currentCard['answer'] = trim($matches[1]);

                if (!empty($currentCard['question']) && !empty($currentCard['answer'])) {
                    $flashcards[] = [
                        'question' => $currentCard['question'],
                        'answer' => $currentCard['answer']
                    ];
                    $currentCard = [];
                }
            }
            // Handle numbered format "1. Question: ..." or "1) Question: ..."
            elseif (preg_match('/^\d+[\.\)]\s*(?:Question|Q):\s*(.+)$/i', $line, $matches)) {
                $currentCard['question'] = trim($matches[1]);
            }
            // Handle "Q:" or "A:" prefixes
            elseif (preg_match('/^Q:\s*(.+)$/i', $line, $matches)) {
                $currentCard['question'] = trim($matches[1]);
            }
            elseif (preg_match('/^A:\s*(.+)$/i', $line, $matches)) {
                $currentCard['answer'] = trim($matches[1]);

                if (!empty($currentCard['question']) && !empty($currentCard['answer'])) {
                    $flashcards[] = [
                        'question' => $currentCard['question'],
                        'answer' => $currentCard['answer']
                    ];
                    $currentCard = [];
                }
            }
        }

        return $flashcards;
    }

    /**
     * Validate content for flashcard generation
     */
    public function validateContent($content)
    {
        if (empty(trim($content))) {
            return [
                'valid' => false,
                'error' => 'Content is empty'
            ];
        }

        // Minimum 2 characters (allows single-word topics like "Python", "Java", etc.)
        if (strlen(trim($content)) < 2) {
            return [
                'valid' => false,
                'error' => 'Content is too short. Please provide at least a topic or title.'
            ];
        }

        $wordCount = str_word_count($content);

        // Note: We allow short content (even single words) because:
        // - Content is treated as a TOPIC/TITLE, not full content
        // - AI generates flashcards ABOUT the topic, not FROM the content
        // - Users can provide just "Python" and get comprehensive flashcards about Python

        if ($wordCount > 50000) {
            return [
                'valid' => false,
                'error' => 'Content is too long. Maximum 50,000 words allowed.'
            ];
        }

        return [
            'valid' => true,
            'word_count' => $wordCount,
            'note' => 'Content treated as topic - AI will generate comprehensive flashcards about this topic'
        ];
    }
}

