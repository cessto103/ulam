<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_forecast_cache', function (Blueprint $table) {
            $table->id();
            $table->string('bucket_key'); // rounded "lat,lng", e.g. "7.19,125.46"
            $table->date('forecast_date');
            $table->string('weather_category');
            $table->unsignedTinyInteger('consecutive_rain_days')->default(0);
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique(['bucket_key', 'forecast_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_forecast_cache');
    }
};
