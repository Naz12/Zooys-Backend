<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class VisitorController extends Controller
{
    public function index()
    {
        $dailyVisitors = Visit::humans()
            ->select(DB::raw("DATE(visited_at) as day"), DB::raw("COUNT(*) as visitors"))
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->limit(14)
            ->pluck('visitors', 'day')
            ->toArray();

        return response()->json([
            'dailyVisitors' => $dailyVisitors,
        ]);
    }
}