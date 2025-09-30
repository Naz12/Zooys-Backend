<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    /**
     * List all active subscription plans
     */
    public function index()
    {
        return response()->json(
            Plan::where('is_active', true)
                ->orderBy('price')
                ->get()
        );
    }
}