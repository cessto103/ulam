<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dislike on posts
        Schema::create('post_dislikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'post_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('dislike_count')->default(0)->after('puso_count');
        });

        // Up / down reactions on recipes
        Schema::create('recipe_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['up', 'down']);
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedInteger('vote_up_count')->default(0)->after('save_count');
            $table->unsignedInteger('vote_down_count')->default(0)->after('vote_up_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_dislikes');
        Schema::table('posts', fn (Blueprint $t) => $t->dropColumn('dislike_count'));
        Schema::dropIfExists('recipe_reactions');
        Schema::table('recipes', fn (Blueprint $t) => $t->dropColumn(['vote_up_count', 'vote_down_count']));
    }
};
