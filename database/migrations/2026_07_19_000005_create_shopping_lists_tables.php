<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['daily', 'event']);
            $table->string('title');
            $table->date('list_date')->nullable(); // daily lists only
            $table->foreignId('meal_plan_id')->nullable()->constrained('meal_plans')->nullOnDelete();
            $table->foreignId('source_recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamp('completed_at')->nullable();
            // Bought total frozen at completion (checked items only).
            $table->decimal('total_spent', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            // One daily list per user per date. Event lists have a NULL
            // list_date, and MySQL allows multiple NULLs in a unique index,
            // so they're unaffected.
            $table->unique(['owner_id', 'type', 'list_date']);
        });

        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quantity')->nullable();
            $table->string('unit', 30)->nullable();
            // e.g. "kailangan: 2 tbsp" — set when a staple/tingi price swap
            // replaced the recipe's proportional amount with a purchasable unit.
            $table->string('needed_note')->nullable();
            // Meal type of the source meal-plan item (null for custom adds and
            // event-list items) — used to rebuild the budget log's
            // expense_breakdown grouping.
            $table->string('meal_type', 20)->nullable();
            $table->string('dish_name')->nullable();
            $table->decimal('est_price', 8, 2)->default(0);
            $table->decimal('actual_price', 8, 2)->nullable();
            $table->boolean('is_checked')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shopping_list_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shopping_list_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_shares');
        Schema::dropIfExists('shopping_list_items');
        Schema::dropIfExists('shopping_lists');
    }
};
