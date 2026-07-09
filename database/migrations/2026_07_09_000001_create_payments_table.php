<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('paymongo');
            // PayMongo payment resource id — unique so webhook retries can't double-record.
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('plan_type', 20);
            $table->unsignedInteger('amount'); // centavos
            $table->string('currency', 3)->default('PHP');
            $table->string('status', 20)->default('paid');
            $table->timestamp('paid_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
