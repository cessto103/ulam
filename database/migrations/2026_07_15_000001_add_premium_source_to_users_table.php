<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Distinguishes a paid PayMongo upgrade from a streak-earned free trial,
            // without touching the Payment ledger (trials aren't real payments).
            $table->string('premium_source')->nullable()->after('premium_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('premium_source');
        });
    }
};
