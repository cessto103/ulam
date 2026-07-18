<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_type', 20);
            // PayMongo's Link id and its auto-generated reference_number. The
            // reference_number is what actually survives onto the resulting
            // Payment (as external_reference_number) once paid — PayMongo does
            // NOT copy the Link's own `metadata` onto that Payment, so the
            // webhook can't recover user_id/plan_type from metadata like the
            // checkout() call that creates the Link might suggest. This table
            // is the real source of truth the webhook looks up by instead.
            $table->string('provider_link_id')->unique();
            $table->string('reference_number')->unique();
            $table->unsignedInteger('amount'); // centavos
            $table->string('status', 20)->default('pending'); // pending|paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
