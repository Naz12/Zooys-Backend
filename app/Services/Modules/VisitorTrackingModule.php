<?php

namespace App\Services\Modules;

use App\Models\VisitorTracking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VisitorTrackingModule
{
    /**
     * Track a visitor visit
     * 
     * @param array $visitData Visit data including:
     *   - tool_id: Tool identifier
     *   - route_path: Route path
     *   - user_id: User ID (optional)
     *   - public_id: Public visitor ID
     *   - session_id: Session ID
     *   - timestamp: Visit timestamp
     *   - referrer: Referrer URL
     *   - location: Location data (array)
     * @param string|null $ipAddress IP address
     * @param string|null $userAgent User agent
     * @return array Result with success status and visit ID
     */
    public function trackVisit(array $visitData, ?string $ipAddress = null, ?string $userAgent = null)
    {
        try {
            Log::info('VisitorTrackingModule: Tracking visit', [
                'tool_id' => $visitData['tool_id'] ?? null,
                'public_id' => $visitData['public_id'] ?? null,
                'session_id' => $visitData['session_id'] ?? null
            ]);

            // Extract device information from user agent
            $deviceInfo = $this->parseUserAgent($userAgent);

            $visit = VisitorTracking::create([
                'tool_id' => $visitData['tool_id'] ?? null,
                'route_path' => $visitData['route_path'] ?? null,
                'user_id' => $visitData['user_id'] ?? null,
                'public_id' => $visitData['public_id'],
                'session_id' => $visitData['session_id'],
                'visited_at' => $visitData['timestamp'] ?? now(),
                'referrer' => $visitData['referrer'] ?? null,
                'location' => $visitData['location'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'browser' => $deviceInfo['browser'] ?? null,
                'os' => $deviceInfo['os'] ?? null,
            ]);

            Log::info('VisitorTrackingModule: Visit tracked successfully', [
                'visit_id' => $visit->id
            ]);

            return [
                'success' => true,
                'visit_id' => $visit->id,
                'message' => 'Visit tracked successfully'
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error tracking visit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get visitor statistics
     * 
     * @param array $filters Filters (date_range, tool_id, user_id, etc.)
     * @return array Statistics
     */
    public function getStatistics(array $filters = [])
    {
        try {
            $query = VisitorTracking::query();

            // Apply filters
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $query->dateRange($filters['start_date'], $filters['end_date']);
            }

            if (isset($filters['tool_id'])) {
                $query->byToolId($filters['tool_id']);
            }

            if (isset($filters['user_id'])) {
                $query->byUserId($filters['user_id']);
            }

            $totalVisits = $query->count();
            $uniqueVisitors = $query->distinct()->count('public_id');
            $uniqueSessions = $query->distinct()->count('session_id');
            $authenticatedVisits = (clone $query)->whereNotNull('user_id')->count();
            $uniqueAuthenticatedUsers = (clone $query)->whereNotNull('user_id')->distinct()->count('user_id');

            // Tool usage statistics
            $toolStats = $query->select('tool_id', DB::raw('COUNT(*) as count'))
                ->groupBy('tool_id')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'tool_id')
                ->toArray();

            // Geographic statistics
            $geoStats = [];
            try {
                $geoQuery = (clone $query)->whereNotNull('location')->get();
                $countryCounts = [];
                
                foreach ($geoQuery as $visit) {
                    $location = $visit->location;
                    if (is_array($location) && isset($location['country'])) {
                        $country = $location['country'];
                        $countryCounts[$country] = ($countryCounts[$country] ?? 0) + 1;
                    }
                }
                
                arsort($countryCounts);
                $geoStats = array_slice($countryCounts, 0, 10, true);
            } catch (\Exception $e) {
                Log::warning('VisitorTrackingModule: Error processing geographic statistics', [
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'statistics' => [
                    'total_visits' => $totalVisits,
                    'unique_visitors' => $uniqueVisitors,
                    'unique_sessions' => $uniqueSessions,
                    'authenticated_visits' => $authenticatedVisits,
                    'anonymous_visits' => $totalVisits - $authenticatedVisits,
                    'unique_authenticated_users' => $uniqueAuthenticatedUsers,
                    'tool_usage' => $toolStats,
                    'top_countries' => $geoStats,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error getting statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get visits with pagination
     * 
     * @param array $filters Filters
     * @param int $perPage Items per page
     * @return array Paginated visits
     */
    public function getVisits(array $filters = [], int $perPage = 50)
    {
        try {
            $query = VisitorTracking::with('user');

            // Apply filters
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $query->dateRange($filters['start_date'], $filters['end_date']);
            }

            if (isset($filters['tool_id'])) {
                $query->byToolId($filters['tool_id']);
            }

            if (isset($filters['user_id'])) {
                $query->byUserId($filters['user_id']);
            }

            if (isset($filters['public_id'])) {
                $query->byPublicId($filters['public_id']);
            }

            if (isset($filters['session_id'])) {
                $query->bySessionId($filters['session_id']);
            }

            $visits = $query->orderBy('visited_at', 'desc')->paginate($perPage);

            return [
                'success' => true,
                'visits' => $visits
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error getting visits', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a single visit by ID
     * 
     * @param int $visitId Visit ID
     * @return array Visit data
     */
    public function getVisit(int $visitId)
    {
        try {
            $visit = VisitorTracking::with('user')->find($visitId);

            if (!$visit) {
                return [
                    'success' => false,
                    'error' => 'Visit not found'
                ];
            }

            return [
                'success' => true,
                'visit' => $visit
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error getting visit', [
                'error' => $e->getMessage(),
                'visit_id' => $visitId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a visit
     * 
     * @param int $visitId Visit ID
     * @param array $data Update data
     * @return array Result
     */
    public function updateVisit(int $visitId, array $data)
    {
        try {
            $visit = VisitorTracking::find($visitId);

            if (!$visit) {
                return [
                    'success' => false,
                    'error' => 'Visit not found'
                ];
            }

            $visit->update($data);

            Log::info('VisitorTrackingModule: Visit updated', [
                'visit_id' => $visitId
            ]);

            return [
                'success' => true,
                'visit' => $visit->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error updating visit', [
                'error' => $e->getMessage(),
                'visit_id' => $visitId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a visit
     * 
     * @param int $visitId Visit ID
     * @return array Result
     */
    public function deleteVisit(int $visitId)
    {
        try {
            $visit = VisitorTracking::find($visitId);

            if (!$visit) {
                return [
                    'success' => false,
                    'error' => 'Visit not found'
                ];
            }

            $visit->delete();

            Log::info('VisitorTrackingModule: Visit deleted', [
                'visit_id' => $visitId
            ]);

            return [
                'success' => true,
                'message' => 'Visit deleted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('VisitorTrackingModule: Error deleting visit', [
                'error' => $e->getMessage(),
                'visit_id' => $visitId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse user agent to extract device information
     * 
     * @param string|null $userAgent User agent string
     * @return array Device information
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [];
        }

        $deviceType = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect OS
        if (preg_match('/windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Check if the visitor tracking module is available
     * 
     * @return bool True if module is available
     */
    public function isAvailable()
    {
        try {
            // Check if table exists and model can be used
            return \Schema::hasTable('visitor_tracking');
        } catch (\Exception $e) {
            return false;
        }
    }
}

