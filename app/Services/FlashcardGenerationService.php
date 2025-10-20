<?php

namespace App\Services;

use App\Services\Modules\AIProcessingModule;
use Illuminate\Support\Facades\Log;

class FlashcardGenerationService
{
    protected $aiProcessingModule;

    public function __construct(AIProcessingModule $aiProcessingModule)
    {
        $this->aiProcessingModule = $aiProcessingModule;
    }

    /**
     * Generate flashcards from extracted content
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

            // Build AI prompt
            $prompt = $this->buildFlashcardPrompt($content, $count, $options);
            
            // Generate flashcards using AI Manager
            $result = $this->aiProcessingModule->generateText($prompt, $options);
            
            if (!$result['generated_content']) {
                throw new \Exception('AI Manager service returned empty response');
            }
            
            $response = $result['generated_content'];

            // Parse response into flashcards
            $flashcards = $this->parseFlashcardResponse($response);
            
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
                    'generation_method' => 'ai_manager',
                    'model_used' => $result['model_used'] ?? 'unknown'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Flashcard generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'flashcards' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build AI prompt for flashcard generation
     */
    private function buildFlashcardPrompt($content, $count, $options = [])
    {
        $difficulty = $options['difficulty'] ?? 'intermediate';
        $style = $options['style'] ?? 'mixed';
        
        // More explicit prompt with examples
        $prompt = "Create exactly {$count} flashcards about: {$content}\n\n";
        $prompt .= "Format each flashcard exactly like this:\n";
        $prompt .= "Question: [Your question here]\n";
        $prompt .= "Answer: [Your answer here]\n\n";
        $prompt .= "Example:\n";
        $prompt .= "Question: What do spiders eat?\n";
        $prompt .= "Answer: Spiders eat insects and other small animals.\n\n";
        $prompt .= "Create {$difficulty} level questions with {$style} question types.\n";
        $prompt .= "Make sure to create exactly {$count} flashcards, one after another.";

        return $prompt;
    }

    /**
     * Parse AI response into flashcard array
     */
    private function parseFlashcardResponse($response)
    {
        try {
            // Try to extract JSON from response
            $jsonStart = strpos($response, '[');
            $jsonEnd = strrpos($response, ']') + 1;
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart);
                $flashcards = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($flashcards)) {
                    return $this->validateFlashcards($flashcards);
                }
            }

            // Fallback: try to parse manually if JSON extraction fails
            return $this->parseFlashcardsManually($response);
            
        } catch (\Exception $e) {
            Log::error('Flashcard parsing error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate and clean flashcards
     */
    private function validateFlashcards($flashcards)
    {
        $validFlashcards = [];
        
        foreach ($flashcards as $card) {
            if (isset($card['question']) && isset($card['answer']) && 
                !empty(trim($card['question'])) && !empty(trim($card['answer']))) {
                
                $validFlashcards[] = [
                    'question' => trim($card['question']),
                    'answer' => trim($card['answer'])
                ];
            }
        }
        
        return $validFlashcards;
    }

    /**
     * Manual parsing fallback
     */
    private function parseFlashcardsManually($response)
    {
        $flashcards = [];
        $lines = explode("\n", $response);
        
        $currentCard = [];
        foreach ($lines as $line) {
            $line = trim($line);
            
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
            // Fallback: try JSON format
            elseif (strpos($line, '"question"') !== false) {
                $currentCard['question'] = $this->extractValue($line);
            } elseif (strpos($line, '"answer"') !== false) {
                $currentCard['answer'] = $this->extractValue($line);
                
                if (!empty($currentCard['question']) && !empty($currentCard['answer'])) {
                    $flashcards[] = [
                        'question' => trim($currentCard['question']),
                        'answer' => trim($currentCard['answer'])
                    ];
                    $currentCard = [];
                }
            }
        }
        
        return $flashcards;
    }

    /**
     * Extract value from JSON line
     */
    private function extractValue($line)
    {
        $start = strpos($line, ':') + 1;
        $value = substr($line, $start);
        $value = trim($value, ' ",\n\r\t');
        return $value;
    }

    /**
     * Check if content is suitable for flashcard generation
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
