<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('icon', 10);
            $table->unsignedSmallInteger('xp_reward');
            $table->enum('category', ['streak', 'budget', 'community', 'recipe', 'market'])->default('budget');
            $table->json('condition')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'earned_at']);
        });

        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('xp_amount');
            $table->string('reason');
            $table->nullableMorphs('source');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->unsignedSmallInteger('xp_reward');
            $table->string('action_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_task_id')->constrained()->cascadeOnDelete();
            $table->date('task_date');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_task_id', 'task_date']);
            $table->index(['user_id', 'task_date']);
        });

        Schema::create('leaderboard_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 20);
            $table->string('scope_value', 100);
            $table->unsignedInteger('xp_total');
            $table->unsignedSmallInteger('rank');
            $table->date('period_date');
            $table->timestamps();

            $table->unique(['user_id', 'scope', 'scope_value', 'period_date']);
            $table->index(['scope', 'scope_value', 'period_date', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_cache');
        Schema::dropIfExists('user_daily_tasks');
        Schema::dropIfExists('daily_tasks');
        Schema::dropIfExists('xp_logs');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
