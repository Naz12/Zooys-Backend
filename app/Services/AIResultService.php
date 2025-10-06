<?php

namespace App\Services;

use App\Models\AIResult;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Log;

class AIResultService
{
    /**
     * Save AI result to database
     */
    public function saveResult($userId, $toolType, $title, $description, $inputData, $resultData, $metadata = [], $fileUploadId = null)
    {
        try {
            $aiResult = AIResult::create([
                'user_id' => $userId,
                'file_upload_id' => $fileUploadId,
                'tool_type' => $toolType,
                'title' => $title,
                'description' => $description,
                'input_data' => $inputData,
                'result_data' => $resultData,
                'metadata' => $metadata,
                'status' => 'completed'
            ]);

            Log::info('AI result saved successfully', [
                'result_id' => $aiResult->id,
                'user_id' => $userId,
                'tool_type' => $toolType
            ]);

            return [
                'success' => true,
                'ai_result' => $aiResult
            ];

        } catch (\Exception $e) {
            Log::error('AI result save failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tool_type' => $toolType
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's AI results
     */
    public function getUserResults($userId, $toolType = null, $perPage = 15, $search = null)
    {
        $query = AIResult::forUser($userId)
            ->with(['fileUpload'])
            ->orderBy('created_at', 'desc');

        if ($toolType) {
            $query->byToolType($toolType);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get specific AI result
     */
    public function getResult($resultId, $userId)
    {
        return AIResult::forUser($userId)
            ->with(['fileUpload'])
            ->find($resultId);
    }

    /**
     * Update AI result
     */
    public function updateResult($resultId, $userId, $data)
    {
        try {
            $aiResult = AIResult::forUser($userId)->find($resultId);
            
            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Result not found'
                ];
            }

            $aiResult->update($data);

            Log::info('AI result updated successfully', [
                'result_id' => $resultId,
                'user_id' => $userId
            ]);

            return [
                'success' => true,
                'ai_result' => $aiResult
            ];

        } catch (\Exception $e) {
            Log::error('AI result update failed', [
                'result_id' => $resultId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete AI result
     */
    public function deleteResult($resultId, $userId)
    {
        try {
            $aiResult = AIResult::forUser($userId)->find($resultId);
            
            if (!$aiResult) {
                return [
                    'success' => false,
                    'error' => 'Result not found'
                ];
            }

            $fileUploadId = $aiResult->file_upload_id;
            $aiResult->delete();

            Log::info('AI result deleted successfully', [
                'result_id' => $resultId,
                'user_id' => $userId,
                'file_upload_id' => $fileUploadId
            ]);

            return [
                'success' => true,
                'message' => 'Result deleted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('AI result deletion failed', [
                'result_id' => $resultId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete result: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get result statistics
     */
    public function getResultStats($userId)
    {
        $totalResults = AIResult::forUser($userId)->count();
        $resultsByTool = AIResult::forUser($userId)
            ->selectRaw('tool_type, COUNT(*) as count')
            ->groupBy('tool_type')
            ->get()
            ->pluck('count', 'tool_type');

        $recentResults = AIResult::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'tool_type', 'created_at']);

        return [
            'total_results' => $totalResults,
            'results_by_tool' => $resultsByTool,
            'recent_results' => $recentResults
        ];
    }
}
