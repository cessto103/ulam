<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replacing the free-text hours field with structured per-day open/close
        // times so the app can compute "open now" / "closed now" against the
        // device clock. No production data depends on the old string format yet.
        Schema::table('tindahan', function (Blueprint $table) {
            $table->dropColumn('store_hours');
        });

        Schema::table('tindahan', function (Blueprint $table) {
            $table->json('store_hours')->nullable()->after('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('tindahan', function (Blueprint $table) {
            $table->dropColumn('store_hours');
        });

        Schema::table('tindahan', function (Blueprint $table) {
            $table->string('store_hours', 100)->nullable()->after('contact_number');
        });
    }
};
