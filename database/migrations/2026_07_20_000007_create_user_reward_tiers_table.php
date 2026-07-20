<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reward_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete (not cascade): an admin deleting a tier that
            // users already earned must not silently erase their history —
            // deactivate the tier (is_active=false) instead. Same precedent
            // as subscriptions.seller_plan_id.
            $table->foreignId('reward_tier_id')->constrained()->restrictOnDelete();
            $table->timestamp('earned_at');
            // null = earned but not yet spent (boost credits banked for later).
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'reward_tier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reward_tiers');
    }
};
