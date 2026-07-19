<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\DailyBudgetLog;
use App\Models\User;

/**
 * The one place today's spend gets logged — used by both the manual
 * "Log Spending" endpoint (BudgetController::log) and shopping-list
 * completion, so the updateOrCreate semantics and the first-log-of-the-day
 * XP award can't drift apart between the two entry points.
 */
class BudgetLogService
{
    public function activePeriod(int $userId): ?BudgetPeriod
    {
        $today = today();

        return BudgetPeriod::where('user_id', $userId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }

    /**
     * @return array{log: DailyBudgetLog, reward: ?array, period: BudgetPeriod}|null
     *         null when the user has no active budget period.
     */
    public function logToday(User $user, float $actualSpent, ?array $expenseBreakdown = null, ?string $notes = null): ?array
    {
        $period = $this->activePeriod($user->id);
        if (! $period) {
            return null;
        }

        $log = DailyBudgetLog::updateOrCreate(
            ['user_id' => $user->id, 'log_date' => today()],
            [
                'budget_period_id'  => $period->id,
                'budgeted_amount'   => $period->daily_food_budget,
                'actual_spent'      => $actualSpent,
                'expense_breakdown' => $expenseBreakdown,
                'notes'             => $notes,
            ]
        );

        $reward = $log->wasRecentlyCreated
            ? app(XpService::class)->award($user, 10, 'log_budget', $log)
            : null;

        return ['log' => $log, 'reward' => $reward, 'period' => $period];
    }
}
