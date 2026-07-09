<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned(); // 1–5
            $table->timestamps();

            $table->unique(['user_id', 'recipe_id']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->default(0)->after('save_count');
            $table->unsignedInteger('ratings_count')->default(0)->after('average_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ratings');
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'ratings_count']);
        });
    }
};
