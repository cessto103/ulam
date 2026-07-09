<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->unsignedTinyInteger('total_days');
            $table->unsignedTinyInteger('household_size');
            $table->decimal('daily_fare', 8, 2)->default(0);
            $table->decimal('daily_allowance', 8, 2)->default(0);
            $table->decimal('daily_food_budget', 8, 2)->storedAs(
                '(total_amount / total_days) - daily_fare - daily_allowance'
            );
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('daily_budget_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_period_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('budgeted_amount', 8, 2);
            $table->decimal('actual_spent', 8, 2)->default(0);
            $table->decimal('saved_amount', 8, 2)->storedAs('budgeted_amount - actual_spent');
            $table->json('expense_breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_budget_logs');
        Schema::dropIfExists('budget_periods');
    }
};
