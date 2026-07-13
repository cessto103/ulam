<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            // 'ulam' = user/admin-created, 'osm' = auto-discovered from OpenStreetMap.
            // Existing rows default to 'ulam' — pre-existing OSM imports can't be
            // told apart retroactively, and mislabeling them as uLam is the safer error.
            $table->string('source', 10)->default('ulam')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
