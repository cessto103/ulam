<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->foreignId('market_id')->nullable()->after('tindahan_id')->constrained('markets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('market_id');
        });
    }
};
