<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // KPIs
        $users = User::count();
        $activeSubs = Subscription::where('active', true)->count();
        $monthlyRevenue = Subscription::where('active', true)
            ->with('plan')
            ->get()
            ->sum(fn($sub) => $sub->plan->price);

        // Handle timeframe filter
        $timeframe = request('timeframe', '1y'); // default 1 year
        $monthsBack = match ($timeframe) {
            '30d' => 1,
            '6m'  => 6,
            default => 12,
        };

        // Revenue + subs trend
        $revenueTrend = Subscription::query()
            ->where('active', true)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                DB::raw("DATE_FORMAT(subscriptions.created_at, '%Y-%m') as month"),
                DB::raw("SUM(plans.price) as revenue"),
                DB::raw("COUNT(subscriptions.id) as subs")
            )
            ->where('subscriptions.created_at', '>=', now()->subMonths($monthsBack))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Daily visitors (last 14 days)
        $dailyVisitors = Visit::query()
            ->where('is_bot', false)
            ->select(DB::raw("DATE(visited_at) as day"), DB::raw("COUNT(*) as visitors"))
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->limit(14)
            ->pluck('visitors', 'day')
            ->toArray();

        return response()->json([
            'users' => $users,
            'activeSubs' => $activeSubs,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueTrend' => $revenueTrend,
            'dailyVisitors' => $dailyVisitors,
        ]);
    }
}