<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);

        return response()->json($users);
    }

    public function create()
    {
        return response()->json([
            'message' => 'Provide user details to create.'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);

        return response()->json([
            'message' => 'User created!',
            'user' => $user,
        ], 201);
    }

    public function edit(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated!',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted!'
        ]);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function activate(User $user)
    {
        $user->update([
            'is_active' => true,
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null
        ]);

        return response()->json([
            'message' => 'User activated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'status' => $user->status,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    public function deactivate(User $user)
    {
        $user->update([
            'is_active' => false,
            'status' => 'inactive'
        ]);

        return response()->json([
            'message' => 'User deactivated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'status' => $user->status,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    public function suspend(User $user)
    {
        $user->update([
            'is_active' => false,
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended by admin'
        ]);

        return response()->json([
            'message' => 'User suspended successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'status' => $user->status,
                'suspended_at' => $user->suspended_at,
                'suspension_reason' => $user->suspension_reason,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    public function subscriptions(User $user)
    {
        $subscription = $user->subscription()->with('plan')->first();
        
        return response()->json([
            'subscription' => $subscription
        ]);
    }

    public function usage(User $user)
    {
        // Get usage statistics for the user
        $totalRequests = $user->histories()->count();
        $thisMonthRequests = $user->histories()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $usageByTool = $user->histories()
            ->join('tools', 'histories.tool_id', '=', 'tools.id')
            ->selectRaw('tools.name, COUNT(*) as count')
            ->groupBy('tools.id', 'tools.name')
            ->get()
            ->pluck('count', 'name');

        return response()->json([
            'usage' => [
                'total_requests' => $totalRequests,
                'this_month' => $thisMonthRequests,
                'by_tool' => $usageByTool
            ]
        ]);
    }

    public function activity(User $user)
    {
        $activities = $user->histories()
            ->with('tool')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'action' => 'tool_usage',
                    'tool' => $history->tool->name,
                    'created_at' => $history->created_at
                ];
            });

        return response()->json([
            'activities' => $activities
        ]);
    }

    public function bulkActivate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $count = User::whereIn('id', $request->user_ids)->update([
            'is_active' => true,
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null
        ]);
        
        return response()->json([
            'message' => "{$count} users activated successfully"
        ]);
    }

    public function bulkDeactivate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $count = User::whereIn('id', $request->user_ids)->update([
            'is_active' => false,
            'status' => 'inactive'
        ]);
        
        return response()->json([
            'message' => "{$count} users deactivated successfully"
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $count = User::whereIn('id', $request->user_ids)->delete();
        
        return response()->json([
            'message' => "{$count} users deleted successfully"
        ]);
    }

    public function export()
    {
        $users = User::all();
        
        // For now, return JSON. In production, you'd generate a CSV
        return response()->json([
            'users' => $users,
            'message' => 'User data exported successfully'
        ]);
    }
}