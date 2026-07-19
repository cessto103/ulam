<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetPeriod;
use App\Models\DailyBudgetLog;
use App\Services\XpService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    private function activePeriod($userId)
    {
        $today = today();
        return BudgetPeriod::where('user_id', $userId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }

    public function current(Request $request)
    {
        $user   = $request->user();
        $period = $this->activePeriod($user->id);

        if (! $period) {
            return response()->json(['has_budget' => false]);
        }

        $today = today();
        $log   = DailyBudgetLog::where('user_id', $user->id)
            ->where('log_date', $today)
            ->first();

        $spent     = $log ? (float) $log->actual_spent : 0;
        $budget    = (float) $period->daily_food_budget;
        $remaining = max(0, $budget - $spent);

        $monthlySavings = DailyBudgetLog::where('budget_period_id', $period->id)
            ->where('log_date', '>=', $today->copy()->startOfMonth())
            ->where('saved_amount', '>', 0)
            ->sum('saved_amount');

        return response()->json([
            'has_budget'       => true,
            'budget'           => round($budget, 2),
            'spent'            => round($spent, 2),
            'remaining'        => round($remaining, 2),
            'monthly_savings'  => round((float) $monthlySavings, 2),
            'has_logged_today' => $log !== null,
            'period'           => [
                'id'                 => $period->id,
                'total_amount'       => (float) $period->total_amount,
                'total_days'         => $period->total_days,
                'daily_food_budget'  => round($budget, 2),
                'household_size'     => $period->household_size,
                'start_date'         => $period->start_date->toDateString(),
                'end_date'           => $period->end_date->toDateString(),
            ],
        ]);
    }

    public function setup(Request $request)
    {
        $data = $request->validate([
            'total_amount'   => ['required', 'numeric', 'min:1'],
            'total_days'     => ['nullable', 'integer', 'min:1', 'max:31'],
            'household_size' => ['required', 'integer', 'min:1', 'max:20'],
            'daily_fare'     => ['nullable', 'numeric', 'min:0'],
            'daily_allowance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user      = $request->user();
        $today     = today();
        $totalDays = $data['total_days'] ?? $today->daysInMonth;

        // 1-day budget: today only; ~month budget: full calendar month; otherwise: today + N-1
        if ($totalDays === 1) {
            $startDate = $today->toDateString();
            $endDate   = $today->toDateString();
        } elseif ($totalDays >= $today->daysInMonth) {
            $startDate = $today->copy()->startOfMonth()->toDateString();
            $endDate   = $today->copy()->endOfMonth()->toDateString();
        } else {
            $startDate = $today->toDateString();
            $endDate   = $today->copy()->addDays($totalDays - 1)->toDateString();
        }

        // Deactivate any running period
        BudgetPeriod::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $period = BudgetPeriod::create([
            'user_id'        => $user->id,
            'total_amount'   => $data['total_amount'],
            'total_days'     => $totalDays,
            'household_size' => $data['household_size'],
            'daily_fare'     => $data['daily_fare'] ?? 0,
            'daily_allowance' => $data['daily_allowance'] ?? 0,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'is_active'      => true,
        ]);

        $user->update(['household_size' => $data['household_size']]);

        $fresh = $period->fresh();

        return response()->json([
            'message' => 'Budget na-set!',
            'period'  => [
                'id'                => $fresh->id,
                'total_amount'      => (float) $fresh->total_amount,
                'daily_food_budget' => (float) $fresh->daily_food_budget,
                'household_size'    => $fresh->household_size,
                'start_date'        => $fresh->start_date->toDateString(),
                'end_date'          => $fresh->end_date->toDateString(),
            ],
        ], 201);
    }

    public function history(Request $request)
    {
        $user   = $request->user();
        $period = $this->activePeriod($user->id);

        if (! $period) {
            return response()->json(['has_budget' => false, 'logs' => []]);
        }

        $logs = DailyBudgetLog::where('budget_period_id', $period->id)
            ->orderBy('log_date')
            ->get()
            ->map(fn($log) => [
                'date'              => $log->log_date->toDateString(),
                'budgeted'          => round((float) $log->budgeted_amount, 2),
                'spent'             => round((float) $log->actual_spent, 2),
                'saved'             => round(max(0, (float) $log->budgeted_amount - (float) $log->actual_spent), 2),
                'expense_breakdown' => $log->expense_breakdown,
                'notes'             => $log->notes,
            ]);

        return response()->json([
            'has_budget' => true,
            'period'     => [
                'start_date'        => $period->start_date->toDateString(),
                'end_date'          => $period->end_date->toDateString(),
                'total_days'        => $period->total_days,
                'daily_food_budget' => round((float) $period->daily_food_budget, 2),
                'total_amount'      => (float) $period->total_amount,
            ],
            'logs' => $logs,
        ]);
    }

    public function forDate(Request $request)
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $user = $request->user();
        $date = $request->query('date');

        // Find the budget period that covered that date
        $period = BudgetPeriod::where('user_id', $user->id)
            ->where('start_date', '<=', $date)
            ->where('end_date',   '>=', $date)
            ->latest('start_date')
            ->first();

        if (!$period) {
            return response()->json(['has_budget' => false]);
        }

        $log  = DailyBudgetLog::where('user_id', $user->id)->where('log_date', $date)->first();
        $budget    = (float) $period->daily_food_budget;
        $spent     = $log ? (float) $log->actual_spent : 0;

        return response()->json([
            'has_budget'        => true,
            'date'              => $date,
            'budget'            => round($budget, 2),
            'spent'             => round($spent, 2),
            'remaining'         => round(max(0, $budget - $spent), 2),
            'has_logged'        => $log !== null,
            'expense_breakdown' => $log?->expense_breakdown,
            'notes'             => $log?->notes,
            'period'            => [
                'id'                 => $period->id,
                'total_days'         => $period->total_days,
                'daily_food_budget'  => round($budget, 2),
                'start_date'         => $period->start_date->toDateString(),
                'end_date'           => $period->end_date->toDateString(),
            ],
        ]);
    }

    public function log(Request $request)
    {
        $data = $request->validate([
            'actual_spent'      => ['required', 'numeric', 'min:0'],
            'expense_breakdown' => ['nullable', 'array'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $result = app(\App\Services\BudgetLogService::class)->logToday(
            $user,
            (float) $data['actual_spent'],
            $data['expense_breakdown'] ?? null,
            $data['notes'] ?? null,
        );

        if (! $result) {
            return response()->json(['message' => 'I-setup muna ang budget.'], 422);
        }

        $reward = $result['reward'];
        $period = $result['period'];

        $budget    = (float) $period->daily_food_budget;
        $spent     = (float) $data['actual_spent'];
        $remaining = max(0, $budget - $spent);

        return response()->json([
            'message'          => 'Nai-log na ang gastos!',
            'xp_earned'        => $reward['xp_awarded'] ?? 0,
            'leveled_up'       => $reward['leveled_up'] ?? false,
            'new_level'        => $reward['new_level'] ?? null,
            'new_achievements' => $reward['new_achievements'] ?? [],
            'remaining'        => round($remaining, 2),
            'saved'            => round(max(0, $budget - $spent), 2),
        ]);
    }
}
