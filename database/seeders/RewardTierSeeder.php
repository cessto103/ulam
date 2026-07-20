<?php

namespace Database\Seeders;

use App\Models\RewardTier;
use App\Models\Task;
use Illuminate\Database\Seeder;

/**
 * A starter set of Reward Tiers built on top of the Tasks catalog
 * (seeded by DatabaseSeeder::seedTasks() before this runs). Deliberately
 * never gates on the 4 inert once-tasks (week-streak, month-streak,
 * budget-saver, super-saver) -- those have no action_type, so they can
 * never auto-complete, which would make any tier requiring them
 * permanently unearnable. Repeating (daily) tasks are safe to gate on:
 * the fulfillment check only asks "has this task ever been completed,"
 * not "is it completed in the current period."
 *
 * Safe to re-run any time -- every row is upserted by title, and the
 * required-tasks pivot is fully re-synced, not just appended to.
 * Standalone: php artisan db:seed --class=RewardTierSeeder
 */
class RewardTierSeeder extends Seeder
{
    public function run(): void
    {
        $taskId = fn (string $slug) => Task::where('slug', $slug)->value('id');

        $tiers = [
            [
                'title' => 'First Bite',
                'description' => 'Welcome to uLam! Earn your first 50 XP to unlock this badge.',
                'icon' => '🎉',
                'reward_type' => 'badge',
                'reward_value' => null,
                'xp_threshold' => 50,
                'required_tasks' => [],
            ],
            [
                'title' => 'Consistent Cook',
                'description' => '3 free days of uLam Premium for building a daily budgeting and meal-planning habit.',
                'icon' => '👨‍🍳',
                'reward_type' => 'premium_days',
                'reward_value' => 3,
                'xp_threshold' => null,
                'required_tasks' => ['log-spending', 'generate-meal-plan'],
            ],
            [
                'title' => 'Price Detective',
                'description' => 'A free 7-day recipe boost for reaching Silver in Presyo Patrol (20 price reports).',
                'icon' => '🔍',
                'reward_type' => 'booster_credit',
                'reward_value' => 7,
                'xp_threshold' => null,
                'required_tasks' => ['price_patrol-silver'],
            ],
            [
                'title' => 'Community Pillar',
                'description' => 'A free 7-day store boost for reaching Gold in Palengke Pro and Silver in Post Creator.',
                'icon' => '🛒',
                'reward_type' => 'store_boost_credit',
                'reward_value' => 7,
                'xp_threshold' => null,
                'required_tasks' => ['palengke_pro-gold', 'post_creator-silver'],
            ],
            [
                'title' => 'Home Chef Diamond',
                'description' => '14 free days of uLam Premium for reaching Diamond in Meal Planner (120 meal plans).',
                'icon' => '💎',
                'reward_type' => 'premium_days',
                'reward_value' => 14,
                'xp_threshold' => null,
                'required_tasks' => ['meal_planner-diamond'],
            ],
            [
                'title' => 'uLam Legend',
                'description' => 'The ultimate badge: reach Diamond in every single Achievement.',
                'icon' => '👑',
                'reward_type' => 'badge',
                'reward_value' => null,
                'xp_threshold' => null,
                'required_tasks' => ['recipe_collector-diamond', 'price_patrol-diamond', 'post_creator-diamond', 'meal_planner-diamond', 'palengke_pro-diamond'],
            ],
        ];

        foreach ($tiers as $t) {
            $taskIds = collect($t['required_tasks'])->map($taskId)->filter()->values();

            $tier = RewardTier::updateOrCreate(
                ['title' => $t['title']],
                [
                    'description'  => $t['description'],
                    'icon'         => $t['icon'],
                    'reward_type'  => $t['reward_type'],
                    'reward_value' => $t['reward_value'],
                    'xp_threshold' => $t['xp_threshold'],
                    'is_active'    => true,
                ]
            );

            $tier->requiredTasks()->sync($taskIds);
        }
    }
}
