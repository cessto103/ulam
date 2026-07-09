<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\CommunityPriceReport;
use App\Models\User;
use App\Models\XpLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class XpService
{
    const LEVEL_THRESHOLDS = [0, 100, 250, 500, 1000, 2000, 3500, 6000, 10000, 15000];

    public function award(User $user, int $xp, string $reason, ?Model $source = null): void
    {
        DB::transaction(function () use ($user, $xp, $reason, $source) {
            XpLog::create([
                'user_id'     => $user->id,
                'xp_amount'   => $xp,
                'reason'      => $reason,
                'source_type' => $source ? get_class($source) : null,
                'source_id'   => $source?->id,
            ]);

            $user->increment('xp', $xp);
            $user->refresh();

            $newLevel = self::calculateLevel($user->xp);
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            $this->checkAchievements($user, $reason);
        });
    }

    public static function calculateLevel(int $xp): int
    {
        $level = 1;
        foreach (self::LEVEL_THRESHOLDS as $i => $threshold) {
            if ($xp >= $threshold) $level = $i + 1;
        }
        return min($level, count(self::LEVEL_THRESHOLDS));
    }

    public static function currentLevelXp(int $level): int
    {
        return self::LEVEL_THRESHOLDS[$level - 1] ?? 0;
    }

    public static function nextLevelXp(int $level): int
    {
        return self::LEVEL_THRESHOLDS[$level] ?? self::LEVEL_THRESHOLDS[count(self::LEVEL_THRESHOLDS) - 1];
    }

    private function checkAchievements(User $user, string $reason): void
    {
        $conditionType = match ($reason) {
            'create_post'        => 'posts_count',
            'generate_meal_plan' => 'meal_plans_count',
            'report_price'       => 'price_reports_count',
            default              => null,
        };

        if (! $conditionType) return;

        $candidates = Achievement::where(
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(`condition`, '$.type'))"),
            $conditionType
        )->get();

        $earnedIds = $user->achievements()->pluck('achievements.id')->flip();

        foreach ($candidates as $achievement) {
            if ($earnedIds->has($achievement->id)) continue;

            $required = $achievement->condition['value'] ?? 1;
            $actual   = match ($conditionType) {
                'posts_count'          => $user->posts()->count(),
                'meal_plans_count'     => $user->mealPlans()->count(),
                'price_reports_count'  => CommunityPriceReport::where('user_id', $user->id)->count(),
                default                => 0,
            };

            if ($actual >= $required) {
                $user->achievements()->attach($achievement->id, ['earned_at' => now()]);

                if ($achievement->xp_reward > 0) {
                    XpLog::create([
                        'user_id'     => $user->id,
                        'xp_amount'   => $achievement->xp_reward,
                        'reason'      => 'achievement_unlocked',
                        'source_type' => Achievement::class,
                        'source_id'   => $achievement->id,
                    ]);
                    $user->increment('xp', $achievement->xp_reward);
                }

                app(\App\Services\NotificationService::class)->send(
                    $user,
                    'achievement',
                    '🏆 Achievement Unlocked!',
                    "Na-unlock mo ang \"{$achievement->name}\"! +{$achievement->xp_reward} XP",
                    ['achievement_id' => $achievement->id],
                    '/(tabs)/awards',
                );
            }
        }
    }
}
