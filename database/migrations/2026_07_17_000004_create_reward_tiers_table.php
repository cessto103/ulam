<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scaffolding only for now — describes a milestone (reach this much
        // XP, unlock this reward) with no fulfillment/redemption logic yet.
        // Reward type/value stays free-text in title/description until the
        // actual reward types are decided.
        Schema::create('reward_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 10)->nullable();
            $table->unsignedInteger('xp_threshold');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tiers');
    }
};
