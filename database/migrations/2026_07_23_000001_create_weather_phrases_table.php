<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('weather_category'); // sunny, cloudy, light_rain, heavy_rain, extended_rain
            $table->string('variant_type')->default('info'); // info, meal_promo, premium_promo
            $table->text('phrase_text'); // supports {{recipe_name}}, {{recipe_author}}, {{rating}}, {{thumbs_count}}, {{days}}
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['weather_category', 'variant_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_phrases');
    }
};
