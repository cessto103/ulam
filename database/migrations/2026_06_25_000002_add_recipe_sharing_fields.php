<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('recipe_id')->nullable()->after('tindahan_id');
            $table->foreign('recipe_id')->references('id')->on('recipes')->nullOnDelete();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedInteger('share_count')->default(0)->after('save_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropColumn('recipe_id');
        });
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('share_count');
        });
    }
};
