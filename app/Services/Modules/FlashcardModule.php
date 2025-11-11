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
            // Validate content
            if (empty(trim($content))) {
                throw new \Exception('No content available for flashcard generation');
            }

            // Check if content is too short
            if (str_word_count($content) < 5) {
                throw new \Exception('Content is too short. Please provide more detailed content (at least 5 words).');
            }

            Log::info("FlashcardModule: Generating flashcards", [
                'content_length' => strlen($content),
                'word_count' => str_word_count($content),
                'count' => $count,
                'options' => $options
            ]);

            // Prepare options for flashcard generation
            $difficulty = $options['difficulty'] ?? 'intermediate';
            $style = $options['style'] ?? 'mixed';
            
            // Build enhanced prompt with instructions
            $prompt = "Create exactly {$count} flashcards from the following content:\n\n{$content}\n\n";
            $prompt .= "Instructions:\n";
            $prompt .= "- Format each flashcard as JSON with 'question' and 'answer' fields\n";
            $prompt .= "- Difficulty level: {$difficulty}\n";
            $prompt .= "- Question style: {$style}\n";
            $prompt .= "- Create exactly {$count} flashcards\n";
            $prompt .= "- Return as a JSON array\n\n";
            $prompt .= "Example format:\n";
            $prompt .= '[{"question": "What is X?", "answer": "X is..."}, {"question": "How does Y work?", "answer": "Y works by..."}]';

            // Use AIProcessingModule to generate flashcards
            $aiOptions = array_merge([
                'model' => $options['model'] ?? config('services.ai_manager.default_model', 'deepseek-chat'),
                'card_count' => $count,
                'difficulty' => $difficulty,
                'style' => $style
            ], $options);

            // Use the generateFlashcards method which uses the 'flashcard' task
            $result = $this->aiProcessingModule->generateFlashcards($prompt, $aiOptions);

            // Parse flashcards from result
            $flashcards = $this->parseFlashcards($result);

            if (empty($flashcards)) {
                throw new \Exception('Failed to parse flashcards from AI response');
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
                'result_keys' => array_keys($result),
                'has_flashcards' => isset($result['flashcards']),
                'has_data' => isset($result['data']),
                'has_insights' => isset($result['insights'])
            ]);

            // Try to get flashcards from result (multiple possible locations)
            $flashcards = $result['flashcards'] ?? $result['data']['flashcards'] ?? [];

            // If flashcards is already an array, validate and return
            if (is_array($flashcards) && !empty($flashcards)) {
                $validated = $this->validateFlashcards($flashcards);
                if (!empty($validated)) {
                    Log::info('FlashcardModule: Successfully parsed flashcards from array', [
                        'count' => count($validated)
                    ]);
                    return $validated;
                }
            }

            // Try to parse from insights, generated_content, or data.raw_output
            $content = $result['insights'] ?? 
                      $result['generated_content'] ?? 
                      $result['data']['raw_output'] ?? 
                      $result['data']['generated_content'] ?? 
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

        foreach ($flashcards as $card) {
            // Handle "question" and "answer" format
            if (isset($card['question']) && isset($card['answer'])) {
                $question = trim($card['question']);
                $answer = trim($card['answer']);
                
                if (!empty($question) && !empty($answer)) {
                    $validFlashcards[] = [
                        'question' => $question,
                        'answer' => $answer
                    ];
                }
            }
            // Handle "front" and "back" format (AI Manager format)
            elseif (isset($card['front']) && isset($card['back'])) {
                $question = trim($card['front']);
                $answer = trim($card['back']);
                
                if (!empty($question) && !empty($answer)) {
                    $validFlashcards[] = [
                        'question' => $question,
                        'answer' => $answer
                    ];
                }
            }
        }

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

        $wordCount = str_word_count($content);

        if ($wordCount < 5) {
            return [
                'valid' => false,
                'error' => 'Content is too short. Please provide more detailed content (at least 5 words).'
            ];
        }

        if ($wordCount > 50000) {
            return [
                'valid' => false,
                'error' => 'Content is too long. Please provide more focused content (max 50,000 words).'
            ];
        }

        return [
            'valid' => true,
            'word_count' => $wordCount
        ];
    }
}

