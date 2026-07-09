<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'connected', 'blocked'])->default('pending');
            $table->timestamps();

            $table->unique(['requester_id', 'recipient_id']);
            $table->index(['recipient_id', 'status']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('post_type', ['recipe_share', 'price_tip', 'budget_win', 'general'])->default('general');
            $table->text('body');
            $table->json('images')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->decimal('budget_amount', 8, 2)->nullable();
            $table->unsignedTinyInteger('serving_size')->nullable();
            $table->boolean('is_sponsored')->default(false);
            $table->foreignId('tindahan_id')->nullable()->constrained('tindahan')->nullOnDelete();
            $table->unsignedInteger('puso_count')->default(0);
            $table->unsignedSmallInteger('comments_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['barangay', 'created_at']);
            $table->index(['post_type', 'created_at']);
        });

        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->enum('reaction', ['puso'])->default('puso');
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('post_comments')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
        });

        Schema::create('post_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('source', ['ai_generated', 'community', 'admin'])->default('ai_generated');
            $table->enum('budget_tag', ['budget_100', 'budget_200', 'budget_400', 'budget_400plus'])->default('budget_200');
            $table->decimal('estimated_cost', 8, 2);
            $table->unsignedTinyInteger('servings');
            $table->unsignedSmallInteger('prep_time_minutes')->default(0);
            $table->unsignedSmallInteger('cook_time_minutes')->default(0);
            $table->json('tags')->nullable();
            $table->json('dietary_flags')->nullable();
            $table->json('steps')->nullable();
            $table->json('tips')->nullable();
            $table->boolean('is_premium_only')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('save_count')->default(0);
            $table->timestamps();

            $table->index(['budget_tag', 'is_published']);
            $table->index(['source', 'is_published']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quantity');
            $table->string('unit', 30);
            $table->decimal('estimated_price', 8, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('recipe_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'recipe_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_book');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('post_saves');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_reactions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('connections');
    }
};
