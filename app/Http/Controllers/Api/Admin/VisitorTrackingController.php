<?php

namespace App\Http\Controllers\Api\Admin;

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
     * Get all visitor tracking records with pagination
     * 
     * GET /api/admin/visitor-tracking
     * 
     * Query parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 50)
     * - start_date: Filter by start date (Y-m-d)
     * - end_date: Filter by end date (Y-m-d)
     * - tool_id: Filter by tool ID
     * - user_id: Filter by user ID
     * - public_id: Filter by public ID
     * - session_id: Filter by session ID
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $filters = [];

            if ($request->has('start_date') && $request->has('end_date')) {
                $filters['start_date'] = $request->input('start_date');
                $filters['end_date'] = $request->input('end_date');
            }

            if ($request->has('tool_id')) {
                $filters['tool_id'] = $request->input('tool_id');
            }

            if ($request->has('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }

            if ($request->has('public_id')) {
                $filters['public_id'] = $request->input('public_id');
            }

            if ($request->has('session_id')) {
                $filters['session_id'] = $request->input('session_id');
            }

            $result = $this->visitorTrackingModule->getVisits($filters, $perPage);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['visits']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching visitor tracking', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch visitor tracking data'
            ], 500);
        }
    }

    /**
     * Get visitor tracking statistics
     * 
     * GET /api/admin/visitor-tracking/statistics
     * 
     * Query parameters:
     * - start_date: Filter by start date (Y-m-d)
     * - end_date: Filter by end date (Y-m-d)
     * - tool_id: Filter by tool ID
     * - user_id: Filter by user ID
     */
    public function statistics(Request $request)
    {
        try {
            $filters = [];

            if ($request->has('start_date') && $request->has('end_date')) {
                $filters['start_date'] = $request->input('start_date');
                $filters['end_date'] = $request->input('end_date');
            }

            if ($request->has('tool_id')) {
                $filters['tool_id'] = $request->input('tool_id');
            }

            if ($request->has('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }

            $result = $this->visitorTrackingModule->getStatistics($filters);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['statistics']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching visitor statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch visitor statistics'
            ], 500);
        }
    }

    /**
     * Get a single visitor tracking record
     * 
     * GET /api/admin/visitor-tracking/{id}
     */
    public function show($id)
    {
        try {
            $result = $this->visitorTrackingModule->getVisit($id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['visit']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching visitor tracking record', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch visitor tracking record'
            ], 500);
        }
    }

    /**
     * Update a visitor tracking record
     * 
     * PUT /api/admin/visitor-tracking/{id}
     * PATCH /api/admin/visitor-tracking/{id}
     * 
     * Body:
     * {
     *   "tool_id": "ai_chat",
     *   "route_path": "/chat",
     *   "user_id": 123,
     *   "referrer": "/",
     *   "location": {...}
     * }
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tool_id' => 'nullable|string|max:255',
                'route_path' => 'nullable|string|max:500',
                'user_id' => 'nullable|integer|exists:users,id',
                'referrer' => 'nullable|string|max:500',
                'location' => 'nullable|array',
                'ip_address' => 'nullable|ip',
                'user_agent' => 'nullable|string|max:500',
                'device_type' => 'nullable|string|max:50',
                'browser' => 'nullable|string|max:100',
                'os' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only([
                'tool_id',
                'route_path',
                'user_id',
                'referrer',
                'location',
                'ip_address',
                'user_agent',
                'device_type',
                'browser',
                'os',
            ]);

            $result = $this->visitorTrackingModule->updateVisit($id, $updateData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visit updated successfully',
                    'data' => $result['visit']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Admin: Error updating visitor tracking', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update visitor tracking record'
            ], 500);
        }
    }

    /**
     * Delete a visitor tracking record
     * 
     * DELETE /api/admin/visitor-tracking/{id}
     */
    public function destroy($id)
    {
        try {
            $result = $this->visitorTrackingModule->deleteVisit($id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visit deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Admin: Error deleting visitor tracking', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete visitor tracking record'
            ], 500);
        }
    }
}

