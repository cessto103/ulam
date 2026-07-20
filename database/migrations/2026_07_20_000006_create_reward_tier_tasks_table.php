<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_tier_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_tier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['reward_tier_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tier_tasks');
    }
};
