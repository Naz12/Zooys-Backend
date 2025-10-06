<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\AIResultService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIResultController extends Controller
{
    protected $aiResultService;

    public function __construct(AIResultService $aiResultService)
    {
        $this->aiResultService = $aiResultService;
    }

    /**
     * Get user's AI results
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $toolType = $request->input('tool_type');
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $results = $this->aiResultService->getUserResults($user->id, $toolType, $perPage, $search);

        return response()->json([
            'ai_results' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total()
            ]
        ]);
    }

    /**
     * Get specific AI result
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->aiResultService->getResult($id, $user->id);

        if (!$result) {
            return response()->json([
                'error' => 'Result not found'
            ], 404);
        }

        return response()->json([
            'ai_result' => $result
        ]);
    }

    /**
     * Update AI result
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = $request->only(['title', 'description', 'metadata']);

        $result = $this->aiResultService->updateResult($id, $user->id, $data);

        if ($result['success']) {
            return response()->json([
                'message' => 'Result updated successfully',
                'ai_result' => $result['ai_result']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
    }

    /**
     * Delete AI result
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $result = $this->aiResultService->deleteResult($id, $user->id);

        if ($result['success']) {
            return response()->json([
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 400);
    }

    /**
     * Get result statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->aiResultService->getResultStats($user->id);

        return response()->json([
            'stats' => $stats
        ]);
    }
}
