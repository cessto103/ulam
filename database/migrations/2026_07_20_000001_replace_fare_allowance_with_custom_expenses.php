<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // daily_food_budget was a MySQL stored generated column derived from
        // daily_fare/daily_allowance on the same row -- it must be dropped
        // before those columns can go, then re-added as a plain column that
        // the controller computes and writes explicitly (custom_expenses is
        // a list, which a same-row generated column can't sum).
        Schema::table('budget_periods', function (Blueprint $table) {
            $table->dropColumn('daily_food_budget');
        });

        Schema::table('budget_periods', function (Blueprint $table) {
            $table->dropColumn(['daily_fare', 'daily_allowance']);
            $table->json('custom_expenses')->nullable()->after('household_size');
            $table->decimal('daily_food_budget', 8, 2)->default(0)->after('custom_expenses');
        });

        // Backfill existing periods: no custom_expenses to carry over, so
        // daily_food_budget is just total_amount / total_days.
        DB::table('budget_periods')->orderBy('id')->chunkById(200, function ($periods) {
            foreach ($periods as $period) {
                DB::table('budget_periods')->where('id', $period->id)->update([
                    'daily_food_budget' => $period->total_days > 0
                        ? round($period->total_amount / $period->total_days, 2)
                        : 0,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('budget_periods', function (Blueprint $table) {
            $table->dropColumn(['custom_expenses', 'daily_food_budget']);
            $table->decimal('daily_fare', 8, 2)->default(0);
            $table->decimal('daily_allowance', 8, 2)->default(0);
        });

        Schema::table('budget_periods', function (Blueprint $table) {
            $table->decimal('daily_food_budget', 8, 2)->storedAs(
                '(total_amount / total_days) - daily_fare - daily_allowance'
            );
        });
    }
};
