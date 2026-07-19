<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\CommunityPriceReport;
use App\Models\DailyTask;
use App\Models\User;
use App\Models\UserDailyTask;
use App\Models\XpLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class XpService
{
    const LEVEL_THRESHOLDS = [0, 100, 250, 500, 1000, 2000, 3500, 6000, 10000, 15000];

    /**
     * @return array{xp_awarded:int,leveled_up:bool,new_level:int,new_achievements:array<int,array{id:int,name:string,icon:?string,xp_reward:int}>}
     */
    public function award(User $user, int $xp, string $reason, ?Model $source = null): array
    {
        return DB::transaction(function () use ($user, $xp, $reason, $source) {
            XpLog::create([
                'user_id'     => $user->id,
                'xp_amount'   => $xp,
                'reason'      => $reason,
                'source_type' => $source ? get_class($source) : null,
                'source_id'   => $source?->id,
            ]);

            $previousLevel = $user->level;
            $user->increment('xp', $xp);
            $user->refresh();

            // Achievements can award bonus XP of their own — check them before
            // the final level calculation so a bonus that crosses a threshold
            // is reflected in leveled_up/new_level, not missed by a level
            // check that ran too early.
            $newAchievements = $this->checkAchievements($user, $reason);
            $this->checkDailyTasks($user, $reason);
            $user->refresh();

            $newLevel = self::calculateLevel($user->xp);
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            return [
                'xp_awarded' => $xp,
                'leveled_up' => $newLevel > $previousLevel,
                'new_level' => $newLevel,
                'new_achievements' => $newAchievements,
            ];
        });
    }

    /**
     * Same ledger as award(), but skipped if this user already earned XP for
     * this reason today. Guard rail for XP that's granted automatically
     * (not from a single deliberate user action) — e.g. "helped with a
     * shared shopping list" — so it can't be farmed by repeating the same
     * trivial action all day.
     *
     * @return array{xp_awarded:int,leveled_up:bool,new_level:int,new_achievements:array<int,array{id:int,name:string,icon:?string,xp_reward:int}>}|null
     *         null when today's cap for this reason was already hit.
     */
    public function awardOncePerDay(User $user, int $xp, string $reason, ?Model $source = null): ?array
    {
        $alreadyToday = XpLog::where('user_id', $user->id)
            ->where('reason', $reason)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadyToday) {
            return null;
        }

        return $this->award($user, $xp, $reason, $source);
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

    /** @return array<int,array{id:int,name:string,icon:?string,xp_reward:int}> */
    private function checkAchievements(User $user, string $reason): array
    {
        $conditionType = match ($reason) {
            'create_post'        => 'posts_count',
            'generate_meal_plan' => 'meal_plans_count',
            'report_price'       => 'price_reports_count',
            default              => null,
        };

        if (! $conditionType) return [];

        $candidates = Achievement::where(
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(`condition`, '$.type'))"),
            $conditionType
        )->get();

        $earnedIds = $user->achievements()->pluck('achievements.id')->flip();
        $newlyUnlocked = [];

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
                    "You unlocked \"{$achievement->title}\"! +{$achievement->xp_reward} XP",
                    ['achievement_id' => $achievement->id],
                    '/(tabs)/awards',
                );

                $newlyUnlocked[] = [
                    'id' => $achievement->id,
                    'name' => $achievement->title,
                    'icon' => $achievement->icon,
                    'xp_reward' => $achievement->xp_reward,
                ];
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Marks any active daily/weekly task matching this action complete for
     * the current period and awards its bonus XP — separate from, and on
     * top of, the XP the triggering action itself already granted. Applies
     * XP directly (not via self::award()) to avoid re-entering this same
     * method for a reason no task will ever match.
     */
    private function checkDailyTasks(User $user, string $reason): void
    {
        $tasks = DailyTask::where('action_type', $reason)
            ->where('is_active', true)
            ->get();

        foreach ($tasks as $task) {
            $periodDate = $task->frequency === 'weekly'
                ? now()->startOfWeek()->toDateString()
                : now()->toDateString();

            $userTask = UserDailyTask::firstOrCreate(
                ['user_id' => $user->id, 'daily_task_id' => $task->id, 'task_date' => $periodDate],
                ['is_completed' => false],
            );

            if ($userTask->is_completed) continue;

            $userTask->update(['is_completed' => true, 'completed_at' => now()]);

            if ($task->xp_reward > 0) {
                XpLog::create([
                    'user_id'     => $user->id,
                    'xp_amount'   => $task->xp_reward,
                    'reason'      => 'daily_task_completed',
                    'source_type' => DailyTask::class,
                    'source_id'   => $task->id,
                ]);
                $user->increment('xp', $task->xp_reward);
            }
        }
    }
}
