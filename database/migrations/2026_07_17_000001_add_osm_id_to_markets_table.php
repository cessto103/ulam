<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            // Identifies the real-world OSM node/way a discovered market came from.
            // Without this, re-discovery matched rows by name+municipality alone,
            // which collides for chain names (7-Eleven, Puregold, "Public Market")
            // and silently overwrites one branch's coordinates with another's.
            $table->unsignedBigInteger('osm_id')->nullable()->after('source');
            $table->string('osm_type', 10)->nullable()->after('osm_id');

            $table->unique(['osm_id', 'osm_type']);
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropUnique(['osm_id', 'osm_type']);
            $table->dropColumn(['osm_id', 'osm_type']);
        });
    }
};
