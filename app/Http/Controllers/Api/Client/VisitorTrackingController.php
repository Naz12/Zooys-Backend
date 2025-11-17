<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Modules\VisitorTrackingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VisitorTrackingController extends Controller
{
    private $visitorTrackingModule;

    public function __construct(VisitorTrackingModule $visitorTrackingModule)
    {
        $this->visitorTrackingModule = $visitorTrackingModule;
    }

    /**
     * Track a visitor visit
     * 
     * POST /api/visitor-tracking
     * 
     * This endpoint is public (no authentication required) but will
     * use the authenticated user's ID if a token is provided.
     * 
     * Body:
     * {
     *   "tool_id": "ai_chat",
     *   "route_path": "/chat",
     *   "public_id": "uuid-v4",
     *   "session_id": "uuid-v4",
     *   "timestamp": "2024-01-15T10:30:00Z",
     *   "referrer": "/",
     *   "location": {
     *     "country": "US",
     *     "country_name": "United States",
     *     "city": "New York",
     *     "region": "NY",
     *     "timezone": "America/New_York"
     *   }
     * }
     */
    public function trackVisit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tool_id' => 'nullable|string|max:255',
                'route_path' => 'nullable|string|max:500',
                'public_id' => 'required|string|max:255',
                'session_id' => 'required|string|max:255',
                'timestamp' => 'nullable|date',
                'referrer' => 'nullable|string|max:500',
                'location' => 'nullable|array',
                'location.country' => 'nullable|string|max:10',
                'location.country_name' => 'nullable|string|max:255',
                'location.city' => 'nullable|string|max:255',
                'location.region' => 'nullable|string|max:255',
                'location.timezone' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            // Get authenticated user ID if available (optional)
            // Manually parse Bearer token since route has no auth middleware
            $userId = null;
            $token = $request->bearerToken();
            
            if ($token) {
                $parts = explode('|', $token);
                if (count($parts) === 2) {
                    $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
                    if ($tokenRecord && $tokenRecord->tokenable) {
                        $userId = $tokenRecord->tokenable_id;
                    }
                }
            }
            
            // Fallback to auth()->id() if token parsing didn't work (for session-based auth)
            if (!$userId) {
                $userId = auth()->id();
            }

            // Prepare visit data
            $visitData = [
                'tool_id' => $request->input('tool_id'),
                'route_path' => $request->input('route_path'),
                'user_id' => $userId,
                'public_id' => $request->input('public_id'),
                'session_id' => $request->input('session_id'),
                'timestamp' => $request->input('timestamp') ?: now(),
                'referrer' => $request->input('referrer'),
                'location' => $request->input('location'),
            ];

            // Get IP address and user agent from request
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            // Track the visit
            $result = $this->visitorTrackingModule->trackVisit($visitData, $ipAddress, $userAgent);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visit tracked successfully',
                    'visit_id' => $result['visit_id']
                ], 201);
            } else {
                Log::error('Visitor tracking failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'visit_data' => $visitData
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to track visit'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Visitor tracking exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while tracking the visit'
            ], 500);
        }
    }
}

