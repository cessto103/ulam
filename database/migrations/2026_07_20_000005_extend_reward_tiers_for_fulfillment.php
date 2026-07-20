<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_tiers', function (Blueprint $table) {
            $table->string('reward_type', 20)->default('badge')->after('icon');
            $table->unsignedInteger('reward_value')->nullable()->after('reward_type');
        });

        // xp_threshold was NOT NULL when a tier could only be XP-gated — tiers
        // can now be gated purely by required tasks instead, so it must accept
        // NULL. Tests run on sqlite (phpunit.xml), which has no MODIFY COLUMN
        // syntax and doesn't enforce this NOT NULL the same way anyway.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reward_tiers MODIFY xp_threshold INT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('reward_tiers', function (Blueprint $table) {
            $table->dropColumn(['reward_type', 'reward_value']);
        });
    }
};
