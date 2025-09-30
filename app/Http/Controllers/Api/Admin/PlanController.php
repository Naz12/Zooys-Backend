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
            'interval' => 'required|string',
            'is_active' => 'boolean',
        ]);

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
            'interval' => 'required|string',
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
}