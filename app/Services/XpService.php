<?php

namespace App\Services;

use App\Models\CommunityPriceReport;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use App\Models\XpLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class XpService
{
    const LEVEL_THRESHOLDS = [0, 100, 250, 500, 1000, 2000, 3500, 6000, 10000, 15000];

    /**
     * @return array{xp_awarded:int,leveled_up:bool,new_level:int,new_achievements:array<int,array{id:int,name:string,icon:?string,xp_reward:int,tier:?string,frequency:string}>}
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

            // Tasks can award bonus XP of their own — check them before the
            // final level calculation so a bonus that crosses a threshold
            // is reflected in leveled_up/new_level, not missed by a level
            // check that ran too early.
            $completedTasks = $this->checkTasks($user, $reason);
            $user->refresh();

            $newLevel = self::calculateLevel($user->xp);
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            return [
                'xp_awarded' => $xp,
                'leveled_up' => $newLevel > $previousLevel,
                'new_level' => $newLevel,
                'new_achievements' => $completedTasks,
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
     * @return array{xp_awarded:int,leveled_up:bool,new_level:int,new_achievements:array<int,array{id:int,name:string,icon:?string,xp_reward:int,tier:?string,frequency:string}>}|null
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

    /**
     * Single unified completion check replacing the old separate
     * achievement/daily-task logic. Every Task row whose action_type
     * matches this reason gets a chance to complete: 'once' tasks are a
     * lifetime counter checked against metricCount() and only ever earned
     * a single time; daily/weekly/monthly tasks track progress_count
     * within the current period and reset every period.
     *
     * @return array<int,array{id:int,name:string,icon:?string,xp_reward:int,tier:?string,frequency:string}>
     */
    private function checkTasks(User $user, string $reason): array
    {
        $tasks = Task::where('action_type', $reason)
            ->where('is_active', true)
            ->orderBy('target_count')
            ->get();

        $completed = [];

        foreach ($tasks as $task) {
            if ($task->frequency === 'once') {
                $alreadyEarned = UserTask::where('user_id', $user->id)
                    ->where('task_id', $task->id)
                    ->exists();
                if ($alreadyEarned) continue;

                $actual = $this->metricCount($user, $task->action_type);
                if ($actual < $task->target_count) continue;

                UserTask::create([
                    'user_id'        => $user->id,
                    'task_id'        => $task->id,
                    'period_date'    => UserTask::LIFETIME_PERIOD,
                    'progress_count' => $actual,
                    'is_completed'   => true,
                    'completed_at'   => now(),
                ]);
            } else {
                $periodDate = $this->periodDateFor($task->frequency);

                $userTask = UserTask::firstOrCreate(
                    ['user_id' => $user->id, 'task_id' => $task->id, 'period_date' => $periodDate],
                    ['is_completed' => false, 'progress_count' => 0],
                );

                if ($userTask->is_completed) continue;

                $userTask->increment('progress_count');
                if ($userTask->progress_count < $task->target_count) continue;

                $userTask->update(['is_completed' => true, 'completed_at' => now()]);
            }

            if ($task->xp_reward > 0) {
                XpLog::create([
                    'user_id'     => $user->id,
                    'xp_amount'   => $task->xp_reward,
                    'reason'      => 'task_completed',
                    'source_type' => Task::class,
                    'source_id'   => $task->id,
                ]);
                $user->increment('xp', $task->xp_reward);
            }

            $title = Task::displayTitle($task, $user->gender, 'tl');

            app(NotificationService::class)->send(
                $user,
                'task_completed',
                "🏆 {$title}!",
                "You unlocked \"{$title}\"! +{$task->xp_reward} XP",
                ['task_id' => $task->id],
                '/(tabs)/awards',
            );

            $completed[] = [
                'id'        => $task->id,
                'name'      => $title,
                'icon'      => $task->icon,
                'xp_reward' => $task->xp_reward,
                'tier'      => $task->tier,
                'frequency' => $task->frequency,
            ];
        }

        return $completed;
    }

    /** Live lifetime count for a given action_type -- shared with UserController::tasks() for tier-progress display. */
    public function metricCount(User $user, string $actionType): int
    {
        return match ($actionType) {
            'generate_meal_plan' => $user->mealPlans()->count(),
            'create_post'        => $user->posts()->count(),
            'report_price'       => CommunityPriceReport::where('user_id', $user->id)->count(),
            'recipe_saved'       => $user->recipeBook()->count(),
            'help_shopping'      => XpLog::where('user_id', $user->id)->where('reason', 'help_shopping')->count(),
            default              => 0,
        };
    }

    private function periodDateFor(string $frequency): string
    {
        return match ($frequency) {
            'weekly'  => now()->startOfWeek()->toDateString(),
            'monthly' => now()->startOfMonth()->toDateString(),
            default   => now()->toDateString(),
        };
    }
}
