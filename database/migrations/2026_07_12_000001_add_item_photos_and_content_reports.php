<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('unit');
        });

        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('unit');
        });

        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('content_type', 20); // post | recipe | tindahan
            $table->unsignedBigInteger('content_id');
            $table->string('reason', 50);
            $table->string('details', 500)->nullable();
            $table->string('status', 20)->default('open'); // open | reviewed | dismissed
            $table->timestamps();

            $table->index(['content_type', 'content_id']);
            $table->unique(['user_id', 'content_type', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::table('market_prices', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
        Schema::table('community_price_reports', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
        Schema::dropIfExists('content_reports');
    }
};
