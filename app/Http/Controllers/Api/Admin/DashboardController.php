<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\MealPlan;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\Request;

// Ports Filament's AiUsageWidget + UserStatsOverview into one JSON payload.
class DashboardController extends Controller
{
    // Sonnet 4.6 pricing per 1M tokens — update if config('services.anthropic.model') changes.
    private const INPUT_PRICE_PER_MILLION = 3.00;
    private const OUTPUT_PRICE_PER_MILLION = 15.00;

    public function stats()
    {
        $thisMonthAiPlans = MealPlan::where('plan_date', '>=', now()->startOfMonth())
            ->where('source', 'ai_generated');

        $promptTokens = (clone $thisMonthAiPlans)->sum('ai_prompt_tokens');
        $completionTokens = (clone $thisMonthAiPlans)->sum('ai_completion_tokens');
        $plansGenerated = (clone $thisMonthAiPlans)->count();
        $estimatedAiCost = ($promptTokens / 1_000_000 * self::INPUT_PRICE_PER_MILLION)
            + ($completionTokens / 1_000_000 * self::OUTPUT_PRICE_PER_MILLION);

        $totalUsers = User::count();
        $premiumUsers = User::where('plan', 'premium')->count();

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'active_today' => User::whereDate('last_active_date', today())->count(),
                'premium' => $premiumUsers,
                'estimated_mrr' => $premiumUsers * (float) AppSetting::get('premium_price_monthly', '59'),
                'banned' => User::whereNotNull('banned_at')->count(),
            ],
            'content' => [
                'total_posts' => Post::count(),
                'total_recipes' => Recipe::count(),
            ],
            // Real money from the payments ledger (amounts stored in centavos).
            'revenue' => [
                'total' => round(Payment::where('status', 'paid')->sum('amount') / 100, 2),
                'this_month' => round(
                    Payment::where('status', 'paid')
                        ->where('paid_at', '>=', now()->startOfMonth())
                        ->sum('amount') / 100,
                    2
                ),
                'payments_count' => Payment::where('status', 'paid')->count(),
            ],
            'ai_usage' => [
                'meal_plans_this_month' => $plansGenerated,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'estimated_cost' => round($estimatedAiCost, 2),
                'note' => 'Meal plan generation only — excludes price-refresh AI calls, which aren\'t token-tracked.',
            ],
        ]);
    }

    // Per-day signups and posts for the growth chart. Note: historical daily-active
    // counts aren't available (only a last_active_date snapshot exists), so this
    // reports what is actually tracked over time.
    public function growth(Request $request)
    {
        $days = min(max($request->integer('days', 30), 7), 90);
        $from = now()->subDays($days - 1)->startOfDay();

        $signups = User::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $posts = Post::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        // Zero-fill so the chart has a point for every day.
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $series[] = [
                'date' => $date,
                'signups' => (int) ($signups[$date] ?? 0),
                'posts' => (int) ($posts[$date] ?? 0),
            ];
        }

        return response()->json(['days' => $days, 'series' => $series]);
    }

    public function xpLeaderboard()
    {
        $leaderboard = User::query()
            ->orderByDesc('xp')
            ->limit(10)
            ->get(['id', 'name', 'municipality', 'level', 'xp', 'streak_days']);

        return response()->json(['leaderboard' => $leaderboard]);
    }
}
