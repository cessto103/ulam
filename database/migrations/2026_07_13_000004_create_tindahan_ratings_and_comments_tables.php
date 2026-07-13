<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindahan_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tindahan_id')->constrained('tindahan')->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned(); // 1–5
            $table->timestamps();

            $table->unique(['user_id', 'tindahan_id']);
        });

        Schema::create('tindahan_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindahan_id')->constrained('tindahan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tindahan_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::table('tindahan', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->default(0)->after('is_verified');
            $table->unsignedInteger('ratings_count')->default(0)->after('average_rating');
            $table->unsignedInteger('comments_count')->default(0)->after('ratings_count');
        });
    }

    public function down(): void
    {
        Schema::table('tindahan', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'ratings_count', 'comments_count']);
        });
        Schema::dropIfExists('tindahan_comments');
        Schema::dropIfExists('tindahan_ratings');
    }
};
