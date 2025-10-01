<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['user', 'plan'])->paginate(15);

        return response()->json($subscriptions);
    }

    public function create()
    {
        $users = User::all();
        $plans = Plan::all();

        return response()->json([
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'stripe_id' => 'nullable|string',
            'stripe_customer_id' => 'nullable|string',
            'active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        // Set default values
        $data['active'] = $data['active'] ?? true;
        $data['starts_at'] = $data['starts_at'] ?? now();
        $data['ends_at'] = $data['ends_at'] ?? now()->addMonth();

        $subscription = Subscription::create($data);

        return response()->json([
            'message' => 'Subscription created!',
            'subscription' => $subscription->load(['user', 'plan']),
        ], 201);
    }

    public function edit(Subscription $subscription)
    {
        $users = User::all();
        $plans = Plan::all();

        return response()->json([
            'subscription' => $subscription->load(['user', 'plan']),
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'stripe_id' => 'nullable|string',
            'stripe_customer_id' => 'nullable|string',
            'active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $subscription->update($data);

        return response()->json([
            'message' => 'Subscription updated!',
            'subscription' => $subscription->load(['user', 'plan']),
        ]);
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted!'
        ]);
    }

    public function show(Subscription $subscription)
    {
        return response()->json($subscription->load(['user', 'plan']));
    }

    public function activate(Subscription $subscription)
    {
        $subscription->update(['active' => true]);
        
        return response()->json([
            'message' => 'Subscription activated successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function pause(Subscription $subscription)
    {
        $subscription->update(['active' => false]);
        
        return response()->json([
            'message' => 'Subscription paused successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function resume(Subscription $subscription)
    {
        $subscription->update(['active' => true]);
        
        return response()->json([
            'message' => 'Subscription resumed successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function cancel(Subscription $subscription)
    {
        $subscription->update(['active' => false]);
        
        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function upgrade(Request $request, Subscription $subscription)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        $subscription->update(['plan_id' => $request->plan_id]);
        
        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function downgrade(Request $request, Subscription $subscription)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        $subscription->update(['plan_id' => $request->plan_id]);
        
        return response()->json([
            'message' => 'Subscription downgraded successfully',
            'subscription' => $subscription->load(['user', 'plan'])
        ]);
    }

    public function analytics()
    {
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('active', true)->count();
        $inactiveSubscriptions = Subscription::where('active', false)->count();
        
        $subscriptionsByPlan = Subscription::with('plan')
            ->selectRaw('plan_id, COUNT(*) as count')
            ->groupBy('plan_id')
            ->get()
            ->map(function ($item) {
                return [
                    'plan_name' => $item->plan->name,
                    'count' => $item->count
                ];
            });

        return response()->json([
            'analytics' => [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'inactive_subscriptions' => $inactiveSubscriptions,
                'subscriptions_by_plan' => $subscriptionsByPlan
            ]
        ]);
    }

    public function revenue()
    {
        $revenue = Subscription::with('plan')
            ->where('active', true)
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        return response()->json([
            'revenue' => [
                'total_revenue' => $revenue,
                'currency' => 'USD'
            ]
        ]);
    }

    public function churn()
    {
        $totalSubscriptions = Subscription::count();
        $cancelledSubscriptions = Subscription::where('active', false)->count();
        $churnRate = $totalSubscriptions > 0 ? ($cancelledSubscriptions / $totalSubscriptions) * 100 : 0;

        return response()->json([
            'churn' => [
                'churn_rate' => round($churnRate, 2),
                'total_subscriptions' => $totalSubscriptions,
                'cancelled_subscriptions' => $cancelledSubscriptions
            ]
        ]);
    }

    public function conversion()
    {
        $totalUsers = User::count();
        $usersWithSubscriptions = User::whereHas('subscription')->count();
        $conversionRate = $totalUsers > 0 ? ($usersWithSubscriptions / $totalUsers) * 100 : 0;

        return response()->json([
            'conversion' => [
                'conversion_rate' => round($conversionRate, 2),
                'total_users' => $totalUsers,
                'users_with_subscriptions' => $usersWithSubscriptions
            ]
        ]);
    }

    public function export()
    {
        $subscriptions = Subscription::with(['user', 'plan'])->get();
        
        return response()->json([
            'subscriptions' => $subscriptions,
            'message' => 'Subscription data exported successfully'
        ]);
    }
}