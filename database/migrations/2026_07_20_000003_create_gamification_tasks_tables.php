<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tier thresholds/XP for the 5 lifetime tier groups. Bronze values match
     * the single-tier thresholds the old Achievement rows already used
     * (except palengke_pro, which has no predecessor).
     */
    private const TIER_GROUPS = [
        'recipe_collector' => [
            'action_type' => 'recipe_saved',
            'icon'        => '📚',
            'title'       => 'Recipe Collector',
            'title_en'    => 'Recipe Collector',
            'tiers'       => [
                'bronze'   => ['target' => 10,  'xp' => 150],
                'silver'   => ['target' => 30,  'xp' => 300],
                'gold'     => ['target' => 75,  'xp' => 600],
                'diamond'  => ['target' => 150, 'xp' => 1200],
            ],
        ],
        'price_patrol' => [
            'action_type' => 'report_price',
            'icon'        => '🔍',
            'title'       => 'Presyo Patrol',
            'title_en'    => 'Price Patrol',
            'tiers'       => [
                'bronze'   => ['target' => 5,   'xp' => 75],
                'silver'   => ['target' => 20,  'xp' => 150],
                'gold'     => ['target' => 50,  'xp' => 350],
                'diamond'  => ['target' => 100, 'xp' => 750],
            ],
        ],
        'palengke_pro' => [
            'action_type' => 'help_shopping',
            'icon'        => '🛒',
            'title'       => 'Palengke Pro',
            'title_en'    => 'Palengke Pro',
            'tiers'       => [
                'bronze'   => ['target' => 5,   'xp' => 50],
                'silver'   => ['target' => 20,  'xp' => 150],
                'gold'     => ['target' => 50,  'xp' => 400],
                'diamond'  => ['target' => 100, 'xp' => 900],
            ],
        ],
        'post_creator' => [
            'action_type' => 'create_post',
            'icon'        => '📝',
            'title'       => 'Post Creator',
            'title_en'    => 'Post Creator',
            'tiers'       => [
                'bronze'   => ['target' => 1,  'xp' => 30],
                'silver'   => ['target' => 10, 'xp' => 100],
                'gold'     => ['target' => 30, 'xp' => 300],
                'diamond'  => ['target' => 75, 'xp' => 750],
            ],
        ],
        'meal_planner' => [
            'action_type' => 'generate_meal_plan',
            'icon'        => '🍽️',
            'title'       => 'Meal Planner',
            'title_en'    => 'Meal Planner',
            'tiers'       => [
                'bronze'   => ['target' => 1,   'xp' => 50],
                'silver'   => ['target' => 15,  'xp' => 150],
                'gold'     => ['target' => 50,  'xp' => 400],
                'diamond'  => ['target' => 120, 'xp' => 900],
            ],
        ],
    ];

    /** tier_group => the old achievements.slug whose earned_at proves the bronze tier. */
    private const BRONZE_PREDECESSOR = [
        'recipe_collector' => 'recipe-collector',
        'price_patrol'     => 'price-reporter',
        'post_creator'     => 'first-post',
        'meal_planner'     => 'first-meal-plan',
        // palengke_pro has no predecessor -- brand new.
    ];

    private const TIER_ORDER = ['bronze', 'silver', 'gold', 'diamond'];

    public function up(): void
    {
        Schema::create('task_action_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->string('icon', 10)->nullable();
            $table->unsignedSmallInteger('xp_reward');
            $table->string('action_type')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'once']);
            $table->unsignedSmallInteger('target_count')->default(1);
            $table->enum('tier', ['bronze', 'silver', 'gold', 'diamond'])->nullable();
            $table->string('tier_group', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('action_type')->references('key')->on('task_action_types')->nullOnDelete();
            $table->index(['action_type', 'is_active']);
            $table->unique(['tier_group', 'tier']);
        });

        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            // 'once' tasks use a fixed sentinel here (not NULL) -- MySQL
            // treats multiple NULLs in a unique index as distinct, which
            // would let a lifetime task be "earned" more than once.
            $table->date('period_date');
            $table->unsignedSmallInteger('progress_count')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'task_id', 'period_date']);
        });

        // MySQL DDL (the drops below) causes an implicit commit, which
        // desyncs Laravel's transaction bookkeeping if it happens inside a
        // DB::transaction() -- so the data migration is wrapped, but the
        // drops run afterward, as plain sequential statements.
        DB::transaction(function () {
            $this->seedActionTypes();
            $taskIds = $this->migrateDailyTasks();
            $taskIds += $this->migrateInertAchievements();
            $tierTaskIds = $this->createTierGroups();

            $this->migrateUserDailyTasks($taskIds);
            $this->backfillTierProgress($tierTaskIds);
        });

        Schema::dropIfExists('user_daily_tasks');
        Schema::dropIfExists('daily_tasks');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }

    private function seedActionTypes(): void
    {
        $now = now();
        $rows = [
            ['key' => 'generate_meal_plan', 'label' => 'Generate a meal plan'],
            ['key' => 'report_price',       'label' => 'Report a price'],
            ['key' => 'create_post',        'label' => 'Create a community post'],
            ['key' => 'log_budget',         'label' => 'Log daily spending'],
            ['key' => 'help_shopping',      'label' => 'Help with a shared shopping list'],
            ['key' => 'recipe_saved',       'label' => 'Save a recipe'],
        ];
        foreach ($rows as &$row) {
            $row['is_active'] = true;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        DB::table('task_action_types')->insert($rows);
    }

    /** @return array<string,int> slug => new tasks.id */
    private function migrateDailyTasks(): array
    {
        $now = now();
        $ids = [];

        foreach (DB::table('daily_tasks')->get() as $dt) {
            // 'check-prices' was never wired to any real XpService::award()
            // call -- null is more honest than preserving a dangling string
            // that will never match a real reason.
            $actionType = $dt->action_type === 'check_prices' ? null : $dt->action_type;

            $id = DB::table('tasks')->insertGetId([
                'slug'            => $dt->slug,
                'title'           => $dt->title,
                'title_en'        => null,
                'description'     => $dt->description,
                'description_en'  => null,
                'icon'            => $dt->icon,
                'xp_reward'       => $dt->xp_reward,
                'action_type'     => $actionType,
                'frequency'       => $dt->frequency,
                'target_count'    => 1,
                'tier'            => null,
                'tier_group'      => null,
                'is_active'       => $dt->is_active,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $ids[$dt->slug] = $id;
        }

        return $ids;
    }

    /** @return array<string,int> slug => new tasks.id, for the 4 inert (streak/savings) achievements. */
    private function migrateInertAchievements(): array
    {
        $now = now();
        $ids = [];
        $inertSlugs = ['week-streak', 'month-streak', 'budget-saver', 'super-saver'];

        foreach (DB::table('achievements')->whereIn('slug', $inertSlugs)->get() as $a) {
            $condition = json_decode($a->condition, true) ?? [];

            $id = DB::table('tasks')->insertGetId([
                'slug'            => $a->slug,
                'title'           => $a->title,
                'title_en'        => $a->title_en,
                'description'     => $a->description,
                'description_en'  => $a->description_en,
                'icon'            => $a->icon,
                'xp_reward'       => $a->xp_reward,
                'action_type'     => null, // no trigger wired yet -- deferred
                'frequency'       => 'once',
                'target_count'    => (int) ($condition['value'] ?? 1),
                'tier'            => null,
                'tier_group'      => null,
                'is_active'       => $a->is_active,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $ids[$a->slug] = $id;
        }

        return $ids;
    }

    /** @return array<string,array<string,int>> tier_group => [tier => tasks.id] */
    private function createTierGroups(): array
    {
        $now = now();
        $result = [];

        foreach (self::TIER_GROUPS as $group => $def) {
            $result[$group] = [];
            foreach ($def['tiers'] as $tier => $spec) {
                $id = DB::table('tasks')->insertGetId([
                    'slug'            => "{$group}-{$tier}",
                    'title'           => $def['title'],
                    'title_en'        => $def['title_en'],
                    'description'     => "Kailangan: {$spec['target']}.",
                    'description_en'  => "Requires: {$spec['target']}.",
                    'icon'            => $def['icon'],
                    'xp_reward'       => $spec['xp'],
                    'action_type'     => $def['action_type'],
                    'frequency'       => 'once',
                    'target_count'    => $spec['target'],
                    'tier'            => $tier,
                    'tier_group'      => $group,
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $result[$group][$tier] = $id;
            }
        }

        return $result;
    }

    private function migrateUserDailyTasks(array $taskIds): void
    {
        $now = now();
        $slugBySlug = DB::table('daily_tasks')->pluck('slug', 'id');

        $rows = [];
        foreach (DB::table('user_daily_tasks')->get() as $udt) {
            $slug = $slugBySlug[$udt->daily_task_id] ?? null;
            if (! $slug || ! isset($taskIds[$slug])) continue;

            $rows[] = [
                'user_id'        => $udt->user_id,
                'task_id'        => $taskIds[$slug],
                'period_date'    => $udt->task_date,
                'progress_count' => $udt->is_completed ? 1 : 0,
                'is_completed'   => $udt->is_completed,
                'completed_at'   => $udt->completed_at,
                'created_at'     => $udt->created_at ?? $now,
                'updated_at'     => $udt->updated_at ?? $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_tasks')->insertOrIgnore($chunk);
        }
    }

    /**
     * For every user, backfill completed user_tasks rows for every tier
     * they already qualify for, computed from CURRENT live data. Where a
     * tier has a direct 1:1 predecessor in the old user_achievements table
     * (the bronze tier of an expanded achievement), use that row's real
     * earned_at instead of now() -- a user whose live count has since
     * dropped below the bronze threshold (e.g. deleted meal plans) must
     * still keep credit for having earned it before. now() is only used
     * for additional tiers a backfill discovers beyond what a historical
     * row proves happened.
     */
    private function backfillTierProgress(array $tierTaskIds): void
    {
        $now = now();

        // slug => [user_id => earned_at] for the 4 achievements being expanded.
        $historicalEarned = [];
        foreach (self::BRONZE_PREDECESSOR as $group => $oldSlug) {
            $achievement = DB::table('achievements')->where('slug', $oldSlug)->first();
            if (! $achievement) continue;
            $historicalEarned[$group] = DB::table('user_achievements')
                ->where('achievement_id', $achievement->id)
                ->pluck('earned_at', 'user_id');
        }

        $userIds = DB::table('users')->pluck('id');
        $rows = [];

        foreach ($userIds as $userId) {
            foreach (self::TIER_GROUPS as $group => $def) {
                $actual = $this->metricCount($userId, $def['action_type']);
                $historicalBronzeEarnedAt = $historicalEarned[$group][$userId] ?? null;

                // Nothing to backfill: no live progress and no historical
                // record of ever having earned this group's predecessor.
                if ($actual <= 0 && ! $historicalBronzeEarnedAt) continue;

                foreach (self::TIER_ORDER as $tier) {
                    $target = $def['tiers'][$tier]['target'];
                    $qualifiesLive = $actual >= $target;
                    // A user whose live count has since dropped below
                    // bronze (e.g. deleted meal plans) still keeps credit
                    // for having earned it before -- checked independently
                    // of the live count, not just as a tie-breaker.
                    $qualifiesHistorically = $tier === 'bronze' && $historicalBronzeEarnedAt;

                    if (! $qualifiesLive && ! $qualifiesHistorically) break; // tiers ascending; nothing higher qualifies either

                    $completedAt = $qualifiesHistorically ? $historicalBronzeEarnedAt : $now;

                    $rows[] = [
                        'user_id'        => $userId,
                        'task_id'        => $tierTaskIds[$group][$tier],
                        'period_date'    => '1970-01-01',
                        'progress_count' => $actual,
                        'is_completed'   => true,
                        'completed_at'   => $completedAt,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_tasks')->insertOrIgnore($chunk);
        }
    }

    private function metricCount(int $userId, string $actionType): int
    {
        return match ($actionType) {
            'generate_meal_plan' => DB::table('meal_plans')->where('user_id', $userId)->count(),
            'create_post'        => DB::table('posts')->where('user_id', $userId)->count(),
            'report_price'       => DB::table('community_price_reports')->where('user_id', $userId)->count(),
            'recipe_saved'       => DB::table('recipe_book')->where('user_id', $userId)->count(),
            'help_shopping'      => DB::table('xp_logs')->where('user_id', $userId)->where('reason', 'help_shopping')->count(),
            default               => 0,
        };
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_action_types');
    }
};
