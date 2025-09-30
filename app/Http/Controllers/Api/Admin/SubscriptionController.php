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
            'active'  => 'boolean',
        ]);

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
            'active'  => 'boolean',
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
}