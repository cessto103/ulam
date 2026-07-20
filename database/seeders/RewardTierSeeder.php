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
                // 'lookup' is the title this row was originally seeded under
                // (English, before title/title_en existed as a Tagalog/English
                // pair like Task already has) -- matched once here so this
                // transition updates the existing 6 rows in place instead of
                // creating 6 duplicates. Every run after this one matches on
                // the real (Tagalog) 'title' below instead, same as any other
                // upserted seed row.
                'lookup' => 'First Bite',
                'title' => 'Unang Subo',
                'title_en' => 'First Bite',
                'description' => 'Maligayang pagdating sa uLam! Kumita ng unang 50 XP para ma-unlock ang badge na ito.',
                'description_en' => 'Welcome to uLam! Earn your first 50 XP to unlock this badge.',
                'icon' => '🎉',
                'reward_type' => 'badge',
                'reward_value' => null,
                'xp_threshold' => 50,
                'required_tasks' => [],
            ],
            [
                'lookup' => 'Consistent Cook',
                'title' => 'Palaging Nagluluto',
                'title_en' => 'Consistent Cook',
                'description' => '3 araw na libreng uLam Premium sa pagbuo ng araw-araw na ugali sa pagbabadyet at pagpaplano ng pagkain.',
                'description_en' => '3 free days of uLam Premium for building a daily budgeting and meal-planning habit.',
                'icon' => '👨‍🍳',
                'reward_type' => 'premium_days',
                'reward_value' => 3,
                'xp_threshold' => null,
                'required_tasks' => ['log-spending', 'generate-meal-plan'],
            ],
            [
                'lookup' => 'Price Detective',
                'title' => 'Detektib ng Presyo',
                'title_en' => 'Price Detective',
                'description' => 'Libreng 7-araw na recipe boost sa pag-abot ng Silver sa Presyo Patrol (20 price report).',
                'description_en' => 'A free 7-day recipe boost for reaching Silver in Presyo Patrol (20 price reports).',
                'icon' => '🔍',
                'reward_type' => 'booster_credit',
                'reward_value' => 7,
                'xp_threshold' => null,
                'required_tasks' => ['price_patrol-silver'],
            ],
            [
                'lookup' => 'Community Pillar',
                'title' => 'Haligi ng Komunidad',
                'title_en' => 'Community Pillar',
                'description' => 'Libreng 7-araw na store boost sa pag-abot ng Gold sa Palengke Pro at Silver sa Post Creator.',
                'description_en' => 'A free 7-day store boost for reaching Gold in Palengke Pro and Silver in Post Creator.',
                'icon' => '🛒',
                'reward_type' => 'store_boost_credit',
                'reward_value' => 7,
                'xp_threshold' => null,
                'required_tasks' => ['palengke_pro-gold', 'post_creator-silver'],
            ],
            [
                'lookup' => 'Home Chef Diamond',
                'title' => 'Diamond Home Chef',
                'title_en' => 'Home Chef Diamond',
                'description' => '14 araw na libreng uLam Premium sa pag-abot ng Diamond sa Meal Planner (120 meal plan).',
                'description_en' => '14 free days of uLam Premium for reaching Diamond in Meal Planner (120 meal plans).',
                'icon' => '💎',
                'reward_type' => 'premium_days',
                'reward_value' => 14,
                'xp_threshold' => null,
                'required_tasks' => ['meal_planner-diamond'],
            ],
            [
                'lookup' => 'uLam Legend',
                'title' => 'Alamat ng uLam',
                'title_en' => 'uLam Legend',
                'description' => 'Ang pinakamataas na badge: umabot ng Diamond sa lahat ng Achievement.',
                'description_en' => 'The ultimate badge: reach Diamond in every single Achievement.',
                'icon' => '👑',
                'reward_type' => 'badge',
                'reward_value' => null,
                'xp_threshold' => null,
                'required_tasks' => ['recipe_collector-diamond', 'price_patrol-diamond', 'post_creator-diamond', 'meal_planner-diamond', 'palengke_pro-diamond'],
            ],
        ];

        foreach ($tiers as $t) {
            $taskIds = collect($t['required_tasks'])->map($taskId)->filter()->values();

            $existing = RewardTier::where('title', $t['lookup'])->orWhere('title', $t['title'])->first();

            $tier = $existing
                ? tap($existing)->update([
                    'title'          => $t['title'],
                    'title_en'       => $t['title_en'],
                    'description'    => $t['description'],
                    'description_en' => $t['description_en'],
                    'icon'           => $t['icon'],
                    'reward_type'    => $t['reward_type'],
                    'reward_value'   => $t['reward_value'],
                    'xp_threshold'   => $t['xp_threshold'],
                    'is_active'      => true,
                ])
                : RewardTier::create([
                    'title'          => $t['title'],
                    'title_en'       => $t['title_en'],
                    'description'    => $t['description'],
                    'description_en' => $t['description_en'],
                    'icon'           => $t['icon'],
                    'reward_type'    => $t['reward_type'],
                    'reward_value'   => $t['reward_value'],
                    'xp_threshold'   => $t['xp_threshold'],
                    'is_active'      => true,
                ]);

            $tier->requiredTasks()->sync($taskIds);
        }
    }
}
