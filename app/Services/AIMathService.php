<?php

namespace App\Services;

use App\Models\MathProblem;
use App\Models\MathSolution;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIMathService
{
    private $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Solve a mathematical problem
     */
    public function solveMathProblem($problemData, $userId)
    {
        try {
            // Create math problem record
            $mathProblem = MathProblem::create([
                'user_id' => $userId,
                'problem_text' => $problemData['problem_text'] ?? null,
                'problem_image' => $problemData['problem_image'] ?? null,
                'problem_type' => $problemData['problem_type'],
                'subject_area' => $problemData['subject_area'] ?? 'general',
                'difficulty_level' => $problemData['difficulty_level'] ?? 'intermediate',
                'metadata' => $problemData['metadata'] ?? []
            ]);

            // Process the problem based on type
            if ($problemData['problem_type'] === 'image') {
                $solution = $this->solveImageProblem($mathProblem);
            } else {
                $solution = $this->solveTextProblem($mathProblem);
            }

            if (!$solution['success']) {
                return [
                    'success' => false,
                    'error' => $solution['error']
                ];
            }

            // Comprehensive data validation and sanitization
            $validatedData = $this->validateAndSanitizeSolutionData($solution);

            // Save solution to database with validated data
            $mathSolution = MathSolution::create([
                'math_problem_id' => $mathProblem->id,
                'solution_method' => $validatedData['method'],
                'step_by_step_solution' => $validatedData['step_by_step'],
                'final_answer' => $validatedData['final_answer'],
                'explanation' => $validatedData['explanation'],
                'verification' => $validatedData['verification'],
                'metadata' => $validatedData['metadata']
            ]);

            return [
                'success' => true,
                'math_problem' => $mathProblem,
                'math_solution' => $mathSolution,
                'solution_data' => $solution
            ];

        } catch (\Exception $e) {
            Log::error('AI Math Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to solve mathematical problem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Solve text-based mathematical problem
     */
    private function solveTextProblem($mathProblem)
    {
        $prompt = $this->buildDetailedMathPrompt($mathProblem->problem_text, $mathProblem->subject_area, $mathProblem->difficulty_level);
        
        $response = $this->openAIService->generateResponse($prompt);

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            return [
                'success' => false,
                'error' => 'OpenAI service unavailable'
            ];
        }

        return $this->parseMathResponse($response);
    }

    /**
     * Solve image-based mathematical problem
     */
    private function solveImageProblem($mathProblem)
    {
        // Get the file path from the universal file upload system
        $imagePath = storage_path('app/public/' . $mathProblem->problem_image);
        
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found at: ' . $imagePath
            ];
        }

        $prompt = $this->buildImageAnalysisPrompt($mathProblem->subject_area, $mathProblem->difficulty_level);
        
        // Use OpenAI Vision API to analyze the image
        $response = $this->openAIService->analyzeImage($imagePath, $prompt);

        if (empty($response) || strpos($response, 'Sorry, I was unable') === 0) {
            return [
                'success' => false,
                'error' => 'OpenAI Vision service unavailable'
            ];
        }

        return $this->parseMathResponse($response);
    }

    /**
     * Build detailed mathematical problem solving prompt for text problems
     */
    private function buildDetailedMathPrompt($problemText, $subjectArea, $difficultyLevel)
    {
        $subjectContext = $this->getSubjectContext($subjectArea);
        $difficultyContext = $this->getDifficultyContext($difficultyLevel);
        
        return "You are an expert mathematics tutor with deep knowledge in {$subjectArea}. Solve the following mathematical problem with comprehensive detail:

PROBLEM: {$problemText}

CONTEXT:
- Subject Area: {$subjectArea} {$subjectContext}
- Difficulty Level: {$difficultyLevel} {$difficultyContext}

REQUIREMENTS:
1. **Problem Analysis**: First, identify what type of problem this is and what concepts are involved
2. **Step-by-Step Solution**: Provide a detailed, numbered solution with clear explanations for each step
3. **Mathematical Reasoning**: Explain the mathematical principles and rules being applied
4. **Final Answer**: State the final answer clearly and prominently
5. **Verification**: Show how to verify the answer is correct
6. **Alternative Methods**: If applicable, mention alternative approaches
7. **Common Mistakes**: Point out common errors students make with this type of problem

FORMAT YOUR RESPONSE AS JSON:
{
    \"method\": \"specific mathematical method used (e.g., 'quadratic formula', 'substitution method', 'Pythagorean theorem')\",
    \"step_by_step\": \"detailed numbered steps with explanations for each step\",
    \"final_answer\": \"the final numerical or algebraic answer\",
    \"explanation\": \"comprehensive explanation of the mathematical concepts and reasoning\",
    \"verification\": \"step-by-step verification process to check the answer\",
    \"metadata\": {
        \"difficulty\": \"{$difficultyLevel}\",
        \"subject\": \"{$subjectArea}\",
        \"concepts_used\": \"list of mathematical concepts applied\",
        \"alternative_methods\": \"any alternative solution approaches\",
        \"common_mistakes\": \"typical errors students make with this problem type\"
    }
}

Make sure each step is clearly explained and the solution is educational and comprehensive.";
    }

    /**
     * Build image analysis prompt for math problems
     */
    private function buildImageAnalysisPrompt($subjectArea, $difficultyLevel)
    {
        $subjectContext = $this->getSubjectContext($subjectArea);
        $difficultyContext = $this->getDifficultyContext($difficultyLevel);
        
        return "You are an expert mathematics tutor. Analyze this image and solve the mathematical problem shown.

CONTEXT:
- Subject Area: {$subjectArea} {$subjectContext}
- Difficulty Level: {$difficultyLevel} {$difficultyContext}

TASKS:
1. **Image Analysis**: Carefully examine the image and identify all mathematical elements (equations, diagrams, text, symbols, graphs, etc.)
2. **Problem Identification**: Determine what mathematical problem is being presented
3. **Data Extraction**: Extract all relevant numbers, variables, equations, and constraints from the image
4. **Solution Process**: Solve the problem step-by-step with detailed explanations
5. **Verification**: Verify your solution and check for any errors

REQUIREMENTS:
- Be thorough in analyzing the image - don't miss any mathematical elements
- If the image contains multiple problems, solve all of them
- If the image is unclear, explain what you can see and make reasonable assumptions
- Provide educational explanations suitable for the difficulty level

FORMAT YOUR RESPONSE AS JSON:
{
    \"method\": \"specific mathematical method used\",
    \"step_by_step\": \"detailed numbered steps with explanations\",
    \"final_answer\": \"the final answer\",
    \"explanation\": \"comprehensive explanation of the solution process\",
    \"verification\": \"verification steps to check the answer\",
    \"metadata\": {
        \"difficulty\": \"{$difficultyLevel}\",
        \"subject\": \"{$subjectArea}\",
        \"image_elements\": \"description of what mathematical elements were found in the image\",
        \"assumptions_made\": \"any assumptions made due to image clarity\",
        \"concepts_used\": \"mathematical concepts applied in the solution\"
    }
}";
    }

    /**
     * Get subject-specific context for prompts
     */
    private function getSubjectContext($subjectArea)
    {
        $contexts = [
            'algebra' => '(focus on equations, variables, and algebraic manipulation)',
            'geometry' => '(focus on shapes, angles, areas, and geometric relationships)',
            'calculus' => '(focus on derivatives, integrals, and limits)',
            'statistics' => '(focus on data analysis, probability, and statistical methods)',
            'trigonometry' => '(focus on angles, triangles, and trigonometric functions)',
            'arithmetic' => '(focus on basic operations and number properties)',
            'maths' => '(general mathematics covering multiple areas)'
        ];
        
        return $contexts[$subjectArea] ?? '(general mathematics)';
    }

    /**
     * Get difficulty-specific context for prompts
     */
    private function getDifficultyContext($difficultyLevel)
    {
        $contexts = [
            'beginner' => '(provide clear, simple explanations suitable for learning)',
            'intermediate' => '(provide detailed explanations with mathematical reasoning)',
            'advanced' => '(provide comprehensive analysis with advanced mathematical concepts)'
        ];
        
        return $contexts[$difficultyLevel] ?? '(provide appropriate level explanations)';
    }

    /**
     * Parse mathematical response from OpenAI
     */
    private function parseMathResponse($content)
    {
        try {
            // Try to extract JSON from the response
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}') + 1;
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart);
                $data = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    return [
                        'success' => true,
                        'method' => $data['method'] ?? 'mathematical analysis',
                        'step_by_step' => $data['step_by_step'] ?? $content,
                        'final_answer' => $data['final_answer'] ?? 'Solution provided',
                        'explanation' => $data['explanation'] ?? 'Mathematical solution provided',
                        'verification' => is_array($data['verification'] ?? []) ? implode(' ', $data['verification']) : ($data['verification'] ?? 'Verification steps provided'),
                        'metadata' => $data['metadata'] ?? []
                    ];
                }
            }

            // If JSON parsing fails, try to extract key information from the text
            $extractedInfo = $this->extractInfoFromText($content);
            
            return [
                'success' => true,
                'method' => $extractedInfo['method'],
                'step_by_step' => $extractedInfo['step_by_step'],
                'final_answer' => $extractedInfo['final_answer'],
                'explanation' => $extractedInfo['explanation'],
                'verification' => $extractedInfo['verification'],
                'metadata' => $extractedInfo['metadata']
            ];

        } catch (\Exception $e) {
            Log::error('Math Response Parsing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to parse mathematical solution: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract information from text response when JSON parsing fails
     */
    private function extractInfoFromText($content)
    {
        // Try to find final answer
        $finalAnswer = 'See detailed solution below';
        if (preg_match('/final answer[:\s]*([^\n]+)/i', $content, $matches)) {
            $finalAnswer = trim($matches[1]);
        } elseif (preg_match('/answer[:\s]*([^\n]+)/i', $content, $matches)) {
            $finalAnswer = trim($matches[1]);
        }

        // Try to find method
        $method = 'mathematical analysis';
        if (preg_match('/method[:\s]*([^\n]+)/i', $content, $matches)) {
            $method = trim($matches[1]);
        }

        // Try to find verification
        $verification = 'Please verify by substituting the answer back into the original problem';
        if (preg_match('/verification[:\s]*([^\n]+)/i', $content, $matches)) {
            $verification = trim($matches[1]);
        } elseif (preg_match('/verify[:\s]*([^\n]+)/i', $content, $matches)) {
            $verification = trim($matches[1]);
        }

        return [
            'method' => $method,
            'step_by_step' => $content,
            'final_answer' => $finalAnswer,
            'explanation' => 'Detailed mathematical solution provided above',
            'verification' => $verification,
            'metadata' => [
                'parsing_method' => 'text_extraction',
                'original_response_length' => strlen($content)
            ]
        ];
    }

    /**
     * Get user's math problems
     */
    public function getUserProblems($userId, $filters = [])
    {
        $query = MathProblem::forUser($userId)->with('solutions');

        if (isset($filters['subject'])) {
            $query->bySubject($filters['subject']);
        }

        if (isset($filters['difficulty'])) {
            $query->byDifficulty($filters['difficulty']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get specific math problem with solutions
     */
    public function getMathProblem($problemId, $userId)
    {
        return MathProblem::forUser($userId)
            ->with('solutions')
            ->findOrFail($problemId);
    }

    /**
     * Get user's math statistics
     */
    public function getUserStats($userId)
    {
        $totalProblems = MathProblem::forUser($userId)->count();
        
        $problemsBySubject = MathProblem::forUser($userId)
            ->selectRaw('subject_area, COUNT(*) as count')
            ->groupBy('subject_area')
            ->pluck('count', 'subject_area')
            ->toArray();
            
        $problemsByDifficulty = MathProblem::forUser($userId)
            ->selectRaw('difficulty_level, COUNT(*) as count')
            ->groupBy('difficulty_level')
            ->pluck('count', 'difficulty_level')
            ->toArray();
            
        $recentActivity = MathProblem::forUser($userId)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
            
        // Calculate success rate (problems with solutions)
        $problemsWithSolutions = MathProblem::forUser($userId)
            ->whereHas('solutions')
            ->count();
            
        $successRate = $totalProblems > 0 ? round(($problemsWithSolutions / $totalProblems) * 100, 2) : 0;
        
        return [
            'total_problems' => $totalProblems,
            'problems_by_subject' => $problemsBySubject,
            'problems_by_difficulty' => $problemsByDifficulty,
            'recent_activity' => $recentActivity,
            'success_rate' => $successRate
        ];
    }

    /**
     * Validate and sanitize solution data before database insertion
     */
    private function validateAndSanitizeSolutionData($solution)
    {
        try {
            // Validate and sanitize method
            $method = $this->sanitizeString($solution['method'] ?? 'mathematical analysis');
            
            // Validate and sanitize step_by_step
            $stepByStep = $this->sanitizeString($solution['step_by_step'] ?? 'Solution steps provided');
            
            // Validate and sanitize final_answer
            $finalAnswer = $this->sanitizeString($solution['final_answer'] ?? 'Solution provided');
            
            // Validate and sanitize explanation
            $explanation = $this->sanitizeString($solution['explanation'] ?? 'Mathematical solution provided');
            
            // Validate and sanitize verification
            $verification = $this->sanitizeVerification($solution['verification'] ?? '');
            
            // Validate and sanitize metadata
            $metadata = $this->sanitizeMetadata($solution['metadata'] ?? []);
            
            Log::info('Math Solution Data Validation', [
                'method_type' => gettype($method),
                'step_by_step_type' => gettype($stepByStep),
                'final_answer_type' => gettype($finalAnswer),
                'explanation_type' => gettype($explanation),
                'verification_type' => gettype($verification),
                'metadata_type' => gettype($metadata)
            ]);
            
            return [
                'method' => $method,
                'step_by_step' => $stepByStep,
                'final_answer' => $finalAnswer,
                'explanation' => $explanation,
                'verification' => $verification,
                'metadata' => $metadata
            ];
            
        } catch (\Exception $e) {
            Log::error('Math Solution Data Validation Error: ' . $e->getMessage());
            
            // Return safe fallback data
            return [
                'method' => 'mathematical analysis',
                'step_by_step' => 'Solution steps provided',
                'final_answer' => 'Solution provided',
                'explanation' => 'Mathematical solution provided',
                'verification' => 'Please verify by substituting the answer back into the original problem',
                'metadata' => ['validation_error' => true, 'error_message' => $e->getMessage()]
            ];
        }
    }

    /**
     * Sanitize string data
     */
    private function sanitizeString($data)
    {
        if (is_array($data)) {
            return implode(' ', array_filter($data, 'is_string'));
        }
        
        if (is_string($data)) {
            return trim($data);
        }
        
        if (is_numeric($data)) {
            return (string) $data;
        }
        
        return 'Invalid data type provided';
    }

    /**
     * Sanitize verification data
     */
    private function sanitizeVerification($verification)
    {
        if (is_array($verification)) {
            return implode(' ', array_filter($verification, 'is_string'));
        }
        
        if (is_string($verification)) {
            return trim($verification);
        }
        
        return 'Please verify by substituting the answer back into the original problem';
    }

    /**
     * Sanitize metadata
     */
    private function sanitizeMetadata($metadata)
    {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return ['raw_data' => $metadata];
        }
        
        if (is_array($metadata)) {
            return $metadata;
        }
        
        return [];
    }
}
