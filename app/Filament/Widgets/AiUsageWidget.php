<?php

namespace App\Filament\Widgets;

use App\Models\MealPlan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiUsageWidget extends BaseWidget
{
    // Sonnet 4.6 pricing per 1M tokens — update if config('services.anthropic.model') changes.
    private const INPUT_PRICE_PER_MILLION = 3.00;
    private const OUTPUT_PRICE_PER_MILLION = 15.00;

    protected function getStats(): array
    {
        $thisMonth = MealPlan::where('plan_date', '>=', now()->startOfMonth())
            ->where('source', 'ai_generated');

        $promptTokens = (clone $thisMonth)->sum('ai_prompt_tokens');
        $completionTokens = (clone $thisMonth)->sum('ai_completion_tokens');
        $plansGenerated = (clone $thisMonth)->count();

        $estimatedCost = ($promptTokens / 1_000_000 * self::INPUT_PRICE_PER_MILLION)
            + ($completionTokens / 1_000_000 * self::OUTPUT_PRICE_PER_MILLION);

        return [
            Stat::make('AI Meal Plans (this month)', number_format($plansGenerated)),
            Stat::make('Tokens Used (this month)', number_format($promptTokens + $completionTokens))
                ->description(number_format($promptTokens) . ' in / ' . number_format($completionTokens) . ' out'),
            Stat::make('Est. Meal-Plan AI Cost', '$' . number_format($estimatedCost, 2))
                ->description('Meal plan generation only — excludes price-refresh AI calls, which aren\'t token-tracked')
                ->color('warning'),
        ];
    }
}
