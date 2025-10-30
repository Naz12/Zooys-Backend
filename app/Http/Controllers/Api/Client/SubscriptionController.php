<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get the current user's active subscription
     */
    public function current(Request $request)
    {
        $subscription = Subscription::with('plan')
            ->where('user_id', $request->user()->id)
            ->where('active', true)
            ->latest('starts_at')
            ->first();

        if (! $subscription) {
            return response()->json([
                'status' => 'none',
                'message' => 'No active subscription found',
            ]);
        }

        return response()->json([
            'id' => $subscription->id,
            'status' => 'active',
            'active' => $subscription->active,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'price' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'limit' => $subscription->plan->limit,
            ],
            'current_usage' => $subscription->current_usage,
            'usage_reset_date' => $subscription->usage_reset_date,
            'billing_cycle_start' => $subscription->billing_cycle_start,
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'grace_period_ends_at' => $subscription->grace_period_ends_at,
            'in_grace_period' => $subscription->isInGracePeriod(),
        ]);
    }

    /**
     * Get full subscription history for the current user
     */
    public function history(Request $request)
    {
        $subscriptions = Subscription::with('plan')
            ->where('user_id', $request->user()->id)
            ->orderBy('starts_at', 'desc')
            ->get();

        return response()->json($subscriptions->map(function ($sub) {
            return [
                'id' => $sub->id,
                'plan' => [
                    'id' => $sub->plan->id,
                    'name' => $sub->plan->name,
                    'price' => $sub->plan->price,
                    'currency' => $sub->plan->currency,
                    'limit' => $sub->plan->limit,
                ],
                'active' => $sub->active,
                'current_usage' => $sub->current_usage,
                'starts_at' => $sub->starts_at,
                'ends_at' => $sub->ends_at,
                'created_at' => $sub->created_at,
            ];
        }));
    }

    /**
     * Get usage statistics for the current user
     */
    public function usage(Request $request)
    {
        $user = $request->user();
        $usageService = app(SubscriptionUsageService::class);
        $stats = $usageService->getUsageStats($user);
        
        return response()->json($stats);
    }
}