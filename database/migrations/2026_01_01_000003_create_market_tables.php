<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['wet_market', 'palengke', 'supermarket', 'grocery', 'tindahan'])->default('wet_market');
            $table->string('barangay', 100);
            $table->string('municipality', 100);
            $table->string('province', 100);
            $table->string('region', 50);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['municipality', 'is_active']);
        });

        Schema::create('tindahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('region', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('gcash_number', 20)->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['municipality', 'is_active']);
        });

        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindahan_id')->nullable()->constrained('tindahan')->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete();
            $table->string('item_name', 100);
            $table->string('category', 50)->nullable();
            $table->decimal('price_per_unit', 8, 2);
            $table->string('unit', 30);
            $table->boolean('is_available')->default(true);
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_name', 'market_id', 'is_available']);
        });

        Schema::create('community_price_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tindahan_id')->nullable()->constrained('tindahan')->nullOnDelete();
            $table->string('item_name', 100);
            $table->string('category', 50)->nullable();
            $table->decimal('reported_price', 8, 2);
            $table->string('unit', 30);
            $table->string('barangay', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->unsignedSmallInteger('upvotes')->default(0);
            $table->unsignedSmallInteger('downvotes')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['item_name', 'municipality']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_custom_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tindahan_id')->nullable()->constrained('tindahan')->nullOnDelete();
            $table->string('item_name', 100);
            $table->decimal('price_per_unit', 8, 2);
            $table->string('unit', 30);
            $table->timestamps();

            $table->index(['user_id', 'item_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_custom_prices');
        Schema::dropIfExists('community_price_reports');
        Schema::dropIfExists('market_prices');
        Schema::dropIfExists('tindahan');
        Schema::dropIfExists('markets');
    }
};
