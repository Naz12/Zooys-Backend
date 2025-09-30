<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
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
            'status' => 'active',
            'plan'   => $subscription->plan->name,
            'price'  => $subscription->plan->price,
            'currency' => $subscription->plan->currency,
            'limit'  => $subscription->plan->limit,
            'starts_at' => $subscription->starts_at,
            'ends_at'   => $subscription->ends_at,
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
                'plan'      => $sub->plan->name,
                'price'     => $sub->plan->price,
                'currency'  => $sub->plan->currency,
                'limit'     => $sub->plan->limit,
                'active'    => $sub->active,
                'starts_at' => $sub->starts_at,
                'ends_at'   => $sub->ends_at,
            ];
        }));
    }
}