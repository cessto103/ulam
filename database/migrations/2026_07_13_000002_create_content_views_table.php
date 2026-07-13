<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_date');
            $table->timestamp('viewed_at');

            $table->unique(['viewable_type', 'viewable_id', 'user_id', 'viewed_date'], 'content_views_unique_per_day');
            $table->index(['viewable_type', 'viewable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
    }
};
