<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Visit;
use App\Models\PaymentHistory;
use App\Services\Modules\SubscriptionBillingModule;
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

    /**
     * Get Monthly Recurring Revenue (MRR)
     */
    public function mrr()
    {
        $mrr = Subscription::where('active', true)
            ->with('plan')
            ->get()
            ->sum(fn($sub) => $sub->plan->price);

        // MRR trend over last 12 months
        $mrrTrend = Subscription::query()
            ->where('active', true)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                DB::raw("DATE_FORMAT(subscriptions.created_at, '%Y-%m') as month"),
                DB::raw("SUM(plans.price) as mrr")
            )
            ->where('subscriptions.created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'current_mrr' => $mrr,
            'mrr_trend' => $mrrTrend,
            'currency' => 'USD',
        ]);
    }

    /**
     * Get Annual Recurring Revenue (ARR)
     */
    public function arr()
    {
        $mrr = Subscription::where('active', true)
            ->with('plan')
            ->get()
            ->sum(fn($sub) => $sub->plan->price);

        $arr = $mrr * 12;

        return response()->json([
            'current_arr' => $arr,
            'mrr' => $mrr,
            'currency' => 'USD',
        ]);
    }

    /**
     * Get subscription growth metrics
     */
    public function subscriptionGrowth()
    {
        $currentMonth = Subscription::where('active', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = Subscription::where('active', true)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $lastMonth > 0 ? 
            round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;

        // Monthly growth trend
        $growthTrend = Subscription::query()
            ->where('active', true)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("COUNT(*) as new_subscriptions")
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'current_month_subscriptions' => $currentMonth,
            'last_month_subscriptions' => $lastMonth,
            'growth_rate' => $growthRate,
            'growth_trend' => $growthTrend,
        ]);
    }

    /**
     * Get revenue breakdown by plan
     */
    public function revenueByPlan()
    {
        $revenueByPlan = Subscription::where('active', true)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select(
                'plans.name',
                'plans.price',
                DB::raw('COUNT(subscriptions.id) as subscriber_count'),
                DB::raw('SUM(plans.price) as total_revenue')
            )
            ->groupBy('plans.id', 'plans.name', 'plans.price')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalRevenue = $revenueByPlan->sum('total_revenue');

        return response()->json([
            'revenue_by_plan' => $revenueByPlan,
            'total_revenue' => $totalRevenue,
            'currency' => 'USD',
        ]);
    }

    /**
     * Get comprehensive subscription analytics
     */
    public function subscriptionAnalytics()
    {
        $billingModule = app(SubscriptionBillingModule::class);
        
        return response()->json([
            'subscription_stats' => $billingModule->getSubscriptionStats(),
            'plan_performance' => $billingModule->getPlanPerformance(),
            'usage_analytics' => $billingModule->getUsageAnalytics(),
            'health_check' => $billingModule->healthCheck(),
        ]);
    }
}