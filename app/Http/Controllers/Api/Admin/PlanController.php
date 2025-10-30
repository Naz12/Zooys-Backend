<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::paginate(10);
        return response()->json($plans);
    }

    public function create()
    {
        // no view, just info for frontend
        return response()->json([
            'message' => 'Provide plan details to create.'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Set default values
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['is_active'] = $data['is_active'] ?? true;

        $plan = Plan::create($data);

        return response()->json([
            'message' => 'Plan created!',
            'plan' => $plan
        ], 201);
    }

    public function edit(Plan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'limit' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $plan->update($data);

        return response()->json([
            'message' => 'Plan updated!',
            'plan' => $plan
        ]);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted!'
        ]);
    }

    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    public function activate(Plan $plan)
    {
        $plan->update(['is_active' => true]);
        
        return response()->json([
            'message' => 'Plan activated successfully',
            'plan' => $plan
        ]);
    }

    public function deactivate(Plan $plan)
    {
        $plan->update(['is_active' => false]);
        
        return response()->json([
            'message' => 'Plan deactivated successfully',
            'plan' => $plan
        ]);
    }

    public function subscriptions(Plan $plan)
    {
        $subscriptions = $plan->subscriptions()->with('user')->get();
        
        return response()->json([
            'plan' => $plan,
            'subscriptions' => $subscriptions
        ]);
    }

    public function analytics(Plan $plan)
    {
        $totalSubscriptions = $plan->subscriptions()->count();
        $activeSubscriptions = $plan->subscriptions()->where('active', true)->count();
        $revenue = $plan->subscriptions()->where('active', true)->count() * $plan->price;
        
        return response()->json([
            'plan' => $plan,
            'analytics' => [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'revenue' => $revenue
            ]
        ]);
    }

    /**
     * Bulk update plans
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:plans,id',
            'updates' => 'required|array',
            'updates.name' => 'nullable|string|max:255',
            'updates.price' => 'nullable|numeric|min:0',
            'updates.currency' => 'nullable|string|max:10',
            'updates.limit' => 'nullable|integer|min:0',
            'updates.is_active' => 'nullable|boolean',
        ]);

        $planIds = $request->plan_ids;
        $updates = $request->updates;
        $updatedCount = 0;

        foreach ($planIds as $planId) {
            try {
                $plan = Plan::findOrFail($planId);
                $plan->update($updates);
                $updatedCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to update plan {$planId}: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => "Bulk update completed. {$updatedCount} plans updated.",
            'updated_count' => $updatedCount,
            'total_requested' => count($planIds),
        ]);
    }

    /**
     * Duplicate a plan
     */
    public function duplicate(Plan $plan)
    {
        $newPlan = $plan->replicate();
        $newPlan->name = $plan->name . ' (Copy)';
        $newPlan->is_active = false; // Start as inactive
        $newPlan->save();

        return response()->json([
            'message' => 'Plan duplicated successfully',
            'original_plan' => $plan,
            'duplicated_plan' => $newPlan,
        ], 201);
    }
}