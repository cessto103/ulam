<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->string('image_url')->nullable()->after('category');
            $table->string('difficulty')->nullable()->after('cook_time_minutes'); // madali | katamtaman | mahirap
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['category', 'image_url', 'difficulty']);
        });
    }
};
