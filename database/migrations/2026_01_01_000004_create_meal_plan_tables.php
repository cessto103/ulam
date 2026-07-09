<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_period_id')->nullable()->constrained('budget_periods')->nullOnDelete();
            $table->date('plan_date');
            $table->enum('source', ['ai_generated', 'template', 'manual'])->default('ai_generated');
            $table->decimal('total_estimated_cost', 8, 2)->nullable();
            $table->unsignedInteger('ai_prompt_tokens')->default(0);
            $table->unsignedInteger('ai_completion_tokens')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'plan_date']);
        });

        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipe_id')->nullable();
            $table->enum('meal_type', ['almusal', 'tanghalian', 'meryenda', 'hapunan']);
            $table->string('dish_name');
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 8, 2);
            $table->unsignedTinyInteger('servings')->default(4);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['meal_plan_id', 'meal_type']);
        });

        Schema::create('meal_plan_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quantity');
            $table->string('unit', 30);
            $table->decimal('estimated_price', 8, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_ingredients');
        Schema::dropIfExists('meal_plan_items');
        Schema::dropIfExists('meal_plans');
    }
};
