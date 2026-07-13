<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\ContentView;
use App\Models\Post;
use App\Models\Recipe;
use App\Models\Tindahan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsightsController extends Controller
{
    private const METRICS = ['posts_count', 'posts_engagement', 'recipes_count', 'recipes_engagement', 'store_popularity'];
    private const PERIODS = ['daily' => 14, 'weekly' => 12, 'monthly' => 12, 'yearly' => 5];

    /** GET /insights/summary — subscription status, boosts with before/during views, quick totals. */
    public function summary(Request $request)
    {
        $user = $request->user();

        $plan = $user->sellerPlan();
        $activeSub = $user->activeSellerSubscription();

        $recipeIds = Recipe::where('user_id', $user->id)->pluck('id');
        $tindahanIds = Tindahan::where('user_id', $user->id)->pluck('id');
        $postIds = Post::where('user_id', $user->id)->pluck('id');

        $boosts = AdBoost::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'expired'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function (AdBoost $boost) {
                $target = $boost->boostable_type === Recipe::class
                    ? Recipe::find($boost->boostable_id, ['id', 'title'])
                    : Tindahan::find($boost->boostable_id, ['id', 'name']);

                $viewsBefore = $viewsDuring = null;
                if ($boost->starts_at) {
                    $windowDays = max(1, $boost->starts_at->diffInDays($boost->expires_at ?? now()));
                    $viewsBefore = ContentView::where('viewable_type', $boost->boostable_type)
                        ->where('viewable_id', $boost->boostable_id)
                        ->whereBetween('viewed_at', [$boost->starts_at->copy()->subDays($windowDays), $boost->starts_at])
                        ->count();
                    $viewsDuring = ContentView::where('viewable_type', $boost->boostable_type)
                        ->where('viewable_id', $boost->boostable_id)
                        ->where('viewed_at', '>=', $boost->starts_at)
                        ->count();
                }

                return [
                    'id' => $boost->id,
                    'target' => $boost->boostable_type === Recipe::class ? 'recipe' : 'tindahan',
                    'target_name' => $target->title ?? $target->name ?? null,
                    'status' => $boost->status,
                    'duration_days' => $boost->duration_days,
                    'starts_at' => $boost->starts_at,
                    'expires_at' => $boost->expires_at,
                    'views_before' => $viewsBefore,
                    'views_during' => $viewsDuring,
                ];
            });

        return response()->json([
            'subscription' => [
                'plan_slug' => $plan->slug,
                'plan_name' => $plan->name,
                'active' => (bool) $activeSub,
                'expires_at' => $activeSub?->expires_at,
            ],
            'boosts' => $boosts,
            'totals' => [
                'posts' => $postIds->count(),
                'recipes' => $recipeIds->count(),
                'stores' => $tindahanIds->count(),
                'views_received' => ContentView::where(function ($q) use ($recipeIds, $tindahanIds, $postIds) {
                    $q->where(fn ($q2) => $q2->where('viewable_type', Recipe::class)->whereIn('viewable_id', $recipeIds))
                      ->orWhere(fn ($q2) => $q2->where('viewable_type', Tindahan::class)->whereIn('viewable_id', $tindahanIds))
                      ->orWhere(fn ($q2) => $q2->where('viewable_type', Post::class)->whereIn('viewable_id', $postIds));
                })->count(),
            ],
        ]);
    }

    /** GET /insights/graph?metric=posts_count&period=daily */
    public function graph(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'metric' => ['required', 'string', 'in:' . implode(',', self::METRICS)],
            'period' => ['required', 'string', 'in:' . implode(',', array_keys(self::PERIODS))],
        ])->validate();

        $user = $request->user();
        [$since, $until] = $this->rangeFor($validated['period']);

        [$query, $dateColumn] = match ($validated['metric']) {
            'posts_count' => [Post::where('user_id', $user->id), 'created_at'],
            'recipes_count' => [Recipe::where('user_id', $user->id), 'created_at'],
            'posts_engagement' => [
                ContentView::where('viewable_type', Post::class)
                    ->whereIn('viewable_id', Post::where('user_id', $user->id)->pluck('id')),
                'viewed_at',
            ],
            'recipes_engagement' => [
                ContentView::where('viewable_type', Recipe::class)
                    ->whereIn('viewable_id', Recipe::where('user_id', $user->id)->pluck('id')),
                'viewed_at',
            ],
            'store_popularity' => [
                ContentView::where('viewable_type', Tindahan::class)
                    ->whereIn('viewable_id', Tindahan::where('user_id', $user->id)->pluck('id')),
                'viewed_at',
            ],
        };

        return response()->json($this->bucketedCount($query, $dateColumn, $validated['period'], $since, $until));
    }

    private function rangeFor(string $period): array
    {
        $until = now();
        $since = match ($period) {
            'daily' => now()->subDays(self::PERIODS['daily'] - 1)->startOfDay(),
            'weekly' => now()->subWeeks(self::PERIODS['weekly'] - 1)->startOfWeek(),
            'monthly' => now()->subMonths(self::PERIODS['monthly'] - 1)->startOfMonth(),
            'yearly' => now()->subYears(self::PERIODS['yearly'] - 1)->startOfYear(),
        };
        return [$since, $until];
    }

    /** Buckets rows by day/week/month/year, filling gaps with zero so the chart's x-axis stays continuous. */
    private function bucketedCount(Builder $query, string $dateColumn, string $period, Carbon $since, Carbon $until): array
    {
        $rows = $query->where($dateColumn, '>=', $since)->get([$dateColumn]);

        $buckets = [];
        $labels = [];
        $cursor = $since->copy();

        while ($cursor <= $until) {
            [$key, $label, $next] = match ($period) {
                'daily' => [$cursor->format('Y-m-d'), $cursor->format('M j'), $cursor->copy()->addDay()],
                'weekly' => [$cursor->format('o-\WW'), $cursor->format('M j'), $cursor->copy()->addWeek()],
                'monthly' => [$cursor->format('Y-m'), $cursor->format('M \'y'), $cursor->copy()->addMonthNoOverflow()],
                'yearly' => [$cursor->format('Y'), $cursor->format('Y'), $cursor->copy()->addYear()],
            };
            $buckets[$key] = 0;
            $labels[$key] = $label;
            $cursor = $next;
        }

        foreach ($rows as $row) {
            $date = Carbon::parse($row->{$dateColumn});
            $key = match ($period) {
                'daily' => $date->format('Y-m-d'),
                'weekly' => $date->format('o-\WW'),
                'monthly' => $date->format('Y-m'),
                'yearly' => $date->format('Y'),
            };
            if (array_key_exists($key, $buckets)) {
                $buckets[$key]++;
            }
        }

        return [
            'labels' => array_values($labels),
            'values' => array_values($buckets),
        ];
    }
}
