<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level'); // 1=warning, 2=restriction, 3=ban
            $table->string('reason', 500);
            $table->foreignId('content_report_id')->nullable()->constrained('content_reports')->nullOnDelete();
            $table->foreignId('listing_report_id')->nullable()->constrained('listing_reports')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            // null = never ages off (ban-tier strikes); set for warning/restriction
            // tiers per config('moderation.strike_expiry_months'). "Active" is always
            // computed on read from this column -- no status field, no background
            // job is load-bearing for the core escalation mechanic.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_strikes');
    }
};
