<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskActionType;
use Illuminate\Database\Seeder;

/**
 * Fresh-install / re-sync source of truth for Tasks + their action types.
 * Safe to re-run any time -- every row is upserted by key (slug for tasks,
 * key for action types), so it also doubles as the way to backfill fields
 * added later (e.g. title_en/description_en) onto rows that already exist
 * from an earlier migration/seed run, such as on a live deploy that
 * migrated its Tasks from legacy daily_tasks data with no English text.
 *
 * Standalone: php artisan db:seed --class=TaskSeeder
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTaskActionTypes();
        $this->seedTasks();
    }

    /**
     * Fresh-install parity for the 6 real action types + 'recipe_saved'
     * (wired in RecipeController::saveToBook() as part of the gamification
     * revamp). Kept in sync with the data migration
     * 2026_07_20_000003_create_gamification_tasks_tables.php by hand --
     * migrations must stay immutable, so this can't just call into it.
     */
    private function seedTaskActionTypes(): void
    {
        $types = [
            ['key' => 'generate_meal_plan', 'label' => 'Generate a meal plan'],
            ['key' => 'report_price',       'label' => 'Report a price'],
            ['key' => 'create_post',        'label' => 'Create a community post'],
            ['key' => 'log_budget',         'label' => 'Log daily spending'],
            ['key' => 'help_shopping',      'label' => 'Help with a shared shopping list'],
            ['key' => 'recipe_saved',       'label' => 'Save a recipe'],
        ];

        foreach ($types as $t) {
            TaskActionType::updateOrCreate(['key' => $t['key']], $t + ['is_active' => true]);
        }
    }

    /**
     * Replaces the old seedAchievements()+seedDailyTasks(). Repeating tasks
     * (daily/weekly) are unchanged from before; the 4 lifetime achievements
     * that had a real trigger are now 4-tier groups (bronze/silver/gold/
     * diamond) instead of a single threshold, plus the new palengke_pro
     * group; the 2 streak + 2 savings achievements carry over inert
     * (action_type null) since nothing triggers them yet.
     */
    private function seedTasks(): void
    {
        $repeating = [
            ['slug' => 'generate-meal-plan', 'title' => 'Mag-generate ng meal plan ngayon', 'title_en' => 'Generate a meal plan today', 'description' => 'I-generate ang iyong daily meal plan para makatipid!', 'description_en' => 'Generate your daily meal plan to save money!', 'icon' => '🍽️', 'xp_reward' => 20, 'action_type' => 'generate_meal_plan', 'frequency' => 'daily', 'is_active' => true],
            // action_type must match the XpService::award() reason actually
            // fired by BudgetController — it's 'log_budget', not 'log_spending'.
            ['slug' => 'log-spending', 'title' => 'I-log ang gastos ngayon', 'title_en' => 'Log today\'s spending', 'description' => 'I-record kung magkano ang iyong ginastos sa pagkain ngayon.', 'description_en' => 'Record how much you spent on food today.', 'icon' => '💰', 'xp_reward' => 10, 'action_type' => 'log_budget', 'frequency' => 'daily', 'is_active' => true],
            // No existing action anywhere awards XP with reason 'check_prices' —
            // this task has nothing to auto-complete against yet. Ships
            // inactive until a real trigger exists.
            ['slug' => 'check-prices', 'title' => 'Tingnan ang presyo ng palengke', 'title_en' => 'Check market prices', 'description' => 'Alamin ang pinakabagong presyo ng mga pangunahing sangkap.', 'description_en' => 'Find out the latest prices of basic ingredients.', 'icon' => '🏷️', 'xp_reward' => 5, 'action_type' => null, 'frequency' => 'daily', 'is_active' => false],
            ['slug' => 'share-tip', 'title' => 'Mag-share ng tip sa komunidad', 'title_en' => 'Share a tip with the community', 'description' => 'Ibahagi ang iyong budget cooking tip sa kapwa!', 'description_en' => 'Share your budget cooking tip with others!', 'icon' => '💬', 'xp_reward' => 15, 'action_type' => 'create_post', 'frequency' => 'daily', 'is_active' => true],
            ['slug' => 'report-price', 'title' => 'Mag-report ng presyo', 'title_en' => 'Report a price', 'description' => 'I-report ang presyo ng isang sangkap sa palengke.', 'description_en' => 'Report the price of an ingredient at the market.', 'icon' => '📢', 'xp_reward' => 10, 'action_type' => 'report_price', 'frequency' => 'daily', 'is_active' => true],
        ];

        foreach ($repeating as $task) {
            Task::updateOrCreate(['slug' => $task['slug']], $task + ['target_count' => 1]);
        }

        $inertOnce = [
            ['slug' => 'week-streak', 'title' => '7 Araw na Streak', 'title_en' => '7-Day Streak', 'description' => 'Bumalik sa app nang 7 magkakasunod na araw!', 'description_en' => 'You came back to the app 7 days in a row!', 'icon' => '🔥', 'xp_reward' => 100, 'target_count' => 7],
            ['slug' => 'month-streak', 'title' => '30 Araw na Streak', 'title_en' => '30-Day Streak', 'description' => 'Isang buwan na consistent ka!', 'description_en' => 'A whole month of consistency!', 'icon' => '💫', 'xp_reward' => 500, 'target_count' => 30],
            ['slug' => 'budget-saver', 'title' => 'Matipid na Nanay/Tatay', 'title_en' => 'Thrifty Parent', 'description' => 'Naka-tipid ng ₱500 sa isang buwan!', 'description_en' => 'Saved ₱500 in a month!', 'icon' => '💰', 'xp_reward' => 200, 'target_count' => 500],
            ['slug' => 'super-saver', 'title' => 'Super Tipid Champion', 'title_en' => 'Super Saver Champion', 'description' => 'Naka-tipid ng ₱2,000 sa isang buwan!', 'description_en' => 'Saved ₱2,000 in a month!', 'icon' => '🏆', 'xp_reward' => 1000, 'target_count' => 2000],
        ];

        foreach ($inertOnce as $a) {
            Task::updateOrCreate(['slug' => $a['slug']], $a + ['action_type' => null, 'frequency' => 'once', 'is_active' => true]);
        }

        $tierGroups = [
            'recipe_collector' => ['action_type' => 'recipe_saved',      'icon' => '📚', 'title' => 'Recipe Collector', 'title_en' => 'Recipe Collector', 'tiers' => ['bronze' => [10, 150], 'silver' => [30, 300], 'gold' => [75, 600], 'diamond' => [150, 1200]]],
            'price_patrol'     => ['action_type' => 'report_price',      'icon' => '🔍', 'title' => 'Presyo Patrol',    'title_en' => 'Price Patrol',      'tiers' => ['bronze' => [5, 75],  'silver' => [20, 150], 'gold' => [50, 350], 'diamond' => [100, 750]]],
            'palengke_pro'     => ['action_type' => 'help_shopping',     'icon' => '🛒', 'title' => 'Palengke Pro',     'title_en' => 'Palengke Pro',      'tiers' => ['bronze' => [5, 50],  'silver' => [20, 150], 'gold' => [50, 400], 'diamond' => [100, 900]]],
            'post_creator'     => ['action_type' => 'create_post',       'icon' => '📝', 'title' => 'Post Creator',     'title_en' => 'Post Creator',      'tiers' => ['bronze' => [1, 30],  'silver' => [10, 100], 'gold' => [30, 300], 'diamond' => [75, 750]]],
            'meal_planner'     => ['action_type' => 'generate_meal_plan', 'icon' => '🍽️', 'title' => 'Meal Planner',    'title_en' => 'Meal Planner',      'tiers' => ['bronze' => [1, 50],  'silver' => [15, 150], 'gold' => [50, 400], 'diamond' => [120, 900]]],
        ];

        foreach ($tierGroups as $group => $def) {
            foreach ($def['tiers'] as $tier => [$target, $xp]) {
                Task::updateOrCreate(
                    ['slug' => "{$group}-{$tier}"],
                    [
                        'title'           => $def['title'],
                        'title_en'        => $def['title_en'],
                        'description'     => "Kailangan: {$target}.",
                        'description_en'  => "Requires: {$target}.",
                        'icon'            => $def['icon'],
                        'xp_reward'       => $xp,
                        'action_type'     => $def['action_type'],
                        'frequency'       => 'once',
                        'target_count'    => $target,
                        'tier'            => $tier,
                        'tier_group'      => $group,
                        'is_active'       => true,
                    ],
                );
            }
        }
    }
}
