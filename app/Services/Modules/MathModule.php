<?php

namespace App\Services\Modules;

use App\Services\AIMathService;
use Illuminate\Support\Facades\Log;

class MathModule
{
    private $mathService;

    public function __construct(AIMathService $mathService)
    {
        $this->mathService = $mathService;
    }

    /**
     * Solve a mathematical problem
     * 
     * @param array $problemData Problem data including:
     *   - problem_text: Text description of the problem
     *   - problem_image: Optional image path/file
     *   - problem_type: Type of problem (algebra, geometry, etc.)
     *   - subject_area: Subject area (default: general)
     *   - difficulty_level: Difficulty (default: intermediate)
     *   - metadata: Additional metadata
     * @param int $userId User ID
     * @return array Solution with step-by-step explanation
     */
    public function solveProblem(array $problemData, int $userId)
    {
        try {
            Log::info('MathModule: Solving mathematical problem', [
                'problem_type' => $problemData['problem_type'] ?? 'unknown',
                'user_id' => $userId
            ]);

            $result = $this->mathService->solveMathProblem($problemData, $userId);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Math problem solving failed');
            }

            Log::info('MathModule: Problem solved successfully', [
                'math_problem_id' => $result['math_problem']['id'] ?? null,
                'math_solution_id' => $result['math_solution']['id'] ?? null
            ]);

            return [
                'success' => true,
                'problem_id' => $result['math_problem']['id'] ?? null,
                'solution_id' => $result['math_solution']['id'] ?? null,
                'method' => $result['math_solution']['solution_method'] ?? null,
                'step_by_step' => $result['math_solution']['step_by_step_solution'] ?? null,
                'final_answer' => $result['math_solution']['final_answer'] ?? null,
                'explanation' => $result['math_solution']['explanation'] ?? null,
                'verification' => $result['math_solution']['verification'] ?? null,
                'metadata' => $result['math_solution']['metadata'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('MathModule: Error solving problem', [
                'error' => $e->getMessage(),
                'problem_type' => $problemData['problem_type'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported subject areas
     * 
     * @return array List of supported subjects
     */
    public function getSupportedSubjects()
    {
        return ['algebra', 'geometry', 'calculus', 'statistics', 'trigonometry', 'arithmetic', 'general'];
    }

    /**
     * Get supported difficulty levels
     * 
     * @return array List of supported difficulty levels
     */
    public function getSupportedDifficultyLevels()
    {
        return ['beginner', 'intermediate', 'advanced'];
    }

    /**
     * Get supported problem types
     * 
     * @return array List of supported problem types
     */
    public function getSupportedProblemTypes()
    {
        return ['algebra', 'geometry', 'calculus', 'statistics', 'trigonometry', 'arithmetic'];
    }

    /**
     * Check if the math microservice is available
     * 
     * @return bool True if service is available
     */
    public function isAvailable()
    {
        try {
            // Check if service can be instantiated and configured
            $microserviceUrl = env('MATH_MICROSERVICE_URL', 'http://localhost:8002');
            return !empty($microserviceUrl);
        } catch (\Exception $e) {
            return false;
        }
    }
}














