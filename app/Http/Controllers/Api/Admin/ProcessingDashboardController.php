<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileProcessingMetricsService;
use App\Services\FileCachingService;
use App\Services\BatchFileProcessingService;
use App\Services\UniversalJobService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProcessingDashboardController extends Controller
{
    private $metricsService;
    private $cachingService;
    private $batchService;
    private $jobService;

    public function __construct(
        FileProcessingMetricsService $metricsService,
        FileCachingService $cachingService,
        BatchFileProcessingService $batchService,
        UniversalJobService $jobService
    ) {
        $this->metricsService = $metricsService;
        $this->cachingService = $cachingService;
        $this->batchService = $batchService;
        $this->jobService = $jobService;
    }

    /**
     * Get processing dashboard overview
     */
    public function getOverview(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'daily');
            $toolType = $request->input('tool_type');
            $fileType = $request->input('file_type');

            $overview = [
                'statistics' => $this->metricsService->getStatistics($toolType, $fileType, $period),
                'performance' => $this->metricsService->getPerformanceMetrics($toolType, $fileType),
                'system_health' => $this->metricsService->getSystemHealth(),
                'cache_stats' => $this->cachingService->getCacheStats(),
                'batch_stats' => $this->batchService->getBatchStats(),
                'job_stats' => $this->jobService->getJobStats()
            ];

            return response()->json([
                'success' => true,
                'data' => $overview,
                'period' => $period,
                'filters' => [
                    'tool_type' => $toolType,
                    'file_type' => $fileType
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Processing dashboard overview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard overview'
            ], 500);
        }
    }

    /**
     * Get processing statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'daily');
            $toolType = $request->input('tool_type');
            $fileType = $request->input('file_type');

            $statistics = $this->metricsService->getStatistics($toolType, $fileType, $period);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            Log::error('Processing statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $toolType = $request->input('tool_type');
            $fileType = $request->input('file_type');

            $metrics = $this->metricsService->getPerformanceMetrics($toolType, $fileType);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            Log::error('Performance metrics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load performance metrics'
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->metricsService->getSystemHealth();

            return response()->json([
                'success' => true,
                'data' => $health
            ]);

        } catch (\Exception $e) {
            Log::error('System health error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load system health'
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $stats = $this->cachingService->getCacheStats($userId);
            $recommendations = $this->cachingService->getCacheRecommendations();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'recommendations' => $recommendations
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cache statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load cache statistics'
            ], 500);
        }
    }

    /**
     * Get batch processing statistics
     */
    public function getBatchStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $stats = $this->batchService->getBatchStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Batch statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load batch statistics'
            ], 500);
        }
    }

    /**
     * Get job statistics
     */
    public function getJobStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $stats = $this->jobService->getJobStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Job statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load job statistics'
            ], 500);
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            
            // This would typically query a database for recent activity
            $activity = [
                'recent_jobs' => [],
                'recent_batches' => [],
                'recent_errors' => [],
                'system_events' => []
            ];

            return response()->json([
                'success' => true,
                'data' => $activity,
                'limit' => $limit
            ]);

        } catch (\Exception $e) {
            Log::error('Recent activity error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent activity'
            ], 500);
        }
    }

    /**
     * Get processing trends
     */
    public function getProcessingTrends(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'weekly');
            $toolType = $request->input('tool_type');
            $fileType = $request->input('file_type');

            $trends = [
                'processing_volume' => $this->getProcessingVolumeTrends($period, $toolType, $fileType),
                'success_rate_trends' => $this->getSuccessRateTrends($period, $toolType, $fileType),
                'processing_time_trends' => $this->getProcessingTimeTrends($period, $toolType, $fileType),
                'error_trends' => $this->getErrorTrends($period, $toolType, $fileType)
            ];

            return response()->json([
                'success' => true,
                'data' => $trends,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            Log::error('Processing trends error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load processing trends'
            ], 500);
        }
    }

    /**
     * Get file type distribution
     */
    public function getFileTypeDistribution(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'daily');
            $toolType = $request->input('tool_type');

            $distribution = $this->metricsService->getStatistics($toolType, null, $period);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'by_file_type' => $distribution['by_file_type'] ?? [],
                    'popular_file_types' => $this->metricsService->getPerformanceMetrics($toolType, null)['popular_file_types'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('File type distribution error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load file type distribution'
            ], 500);
        }
    }

    /**
     * Get tool usage distribution
     */
    public function getToolUsageDistribution(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'daily');
            $fileType = $request->input('file_type');

            $distribution = $this->metricsService->getStatistics(null, $fileType, $period);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'by_tool_type' => $distribution['by_tool_type'] ?? [],
                    'popular_tools' => $this->metricsService->getPerformanceMetrics(null, $fileType)['popular_tools'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Tool usage distribution error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load tool usage distribution'
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $fileId = $request->input('file_id');

            if ($fileId) {
                $this->cachingService->clearFileCache($fileId);
                $message = "Cache cleared for file {$fileId}";
            } elseif ($userId) {
                $this->cachingService->clearUserCache($userId);
                $message = "Cache cleared for user {$userId}";
            } else {
                $this->cachingService->clearExpiredCache();
                $message = "Expired cache cleared";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Clear cache error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Warm up cache
     */
    public function warmUpCache(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $limit = $request->input('limit', 10);

            $warmed = $this->cachingService->warmUpCache($userId, $limit);

            return response()->json([
                'success' => true,
                'message' => "Cache warmed up for {$warmed} files"
            ]);

        } catch (\Exception $e) {
            Log::error('Warm up cache error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to warm up cache'
            ], 500);
        }
    }

    /**
     * Helper methods for trends
     */
    private function getProcessingVolumeTrends($period, $toolType, $fileType)
    {
        // Mock data - would typically query database
        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Files Processed',
                    'data' => [120, 150, 180, 200, 160, 140, 100],
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)'
                ]
            ]
        ];
    }

    private function getSuccessRateTrends($period, $toolType, $fileType)
    {
        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Success Rate (%)',
                    'data' => [95, 97, 94, 96, 98, 95, 93],
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)'
                ]
            ]
        ];
    }

    private function getProcessingTimeTrends($period, $toolType, $fileType)
    {
        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Average Processing Time (s)',
                    'data' => [45, 42, 48, 40, 38, 44, 46],
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)'
                ]
            ]
        ];
    }

    private function getErrorTrends($period, $toolType, $fileType)
    {
        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Errors',
                    'data' => [5, 3, 8, 4, 2, 6, 7],
                    'borderColor' => 'rgb(255, 159, 64)',
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)'
                ]
            ]
        ];
    }
}


































