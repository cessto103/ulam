<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\Recipe;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeToday = User::whereDate('last_active_date', today())->count();
        $premiumUsers = User::where('plan', 'premium')->count();
        $bannedUsers = User::whereNotNull('banned_at')->count();

        // Rough MRR estimate — Premium is ₱59/month per project pricing plan.
        // Update PREMIUM_MONTHLY_PRICE if pricing changes.
        $premiumMonthlyPrice = 59;
        $estimatedMrr = $premiumUsers * $premiumMonthlyPrice;

        return [
            Stat::make('Total Users', number_format($totalUsers)),
            Stat::make('Active Today', number_format($activeToday))
                ->description('Opened the app today')
                ->color('success'),
            Stat::make('Premium Users', number_format($premiumUsers))
                ->description('₱' . number_format($estimatedMrr) . ' est. MRR')
                ->color('warning'),
            Stat::make('Banned Users', number_format($bannedUsers))
                ->color($bannedUsers > 0 ? 'danger' : 'gray'),
            Stat::make('Total Posts', number_format(Post::count())),
            Stat::make('Total Recipes', number_format(Recipe::count())),
        ];
    }
}
