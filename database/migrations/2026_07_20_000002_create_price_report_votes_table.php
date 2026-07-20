<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_report_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_price_report_id')->constrained()->cascadeOnDelete();
            $table->enum('vote', ['up', 'down']);
            $table->timestamps();

            $table->unique(['user_id', 'community_price_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_report_votes');
    }
};
