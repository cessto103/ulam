<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_verified');
            $table->string('declined_reason', 100)->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('declined_reason');
        });

        // Reports that existed before owner review was introduced stay visible.
        DB::table('community_price_reports')->update(['status' => 'accepted']);
    }

    public function down(): void
    {
        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->dropColumn(['status', 'declined_reason', 'reviewed_at']);
        });
    }
};
