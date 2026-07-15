<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('sections')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Carry forward whatever was already configured under the old single-blob
        // AppSetting key (if the admin had touched the Theme page before presets
        // existed) as the initial, active "Default" preset. Empty sections just
        // mean "use the app's compiled-in look" — same as before.
        $existing = DB::table('app_settings')->where('key', 'theme_sections')->value('value');

        DB::table('theme_presets')->insert([
            'name'       => 'Default',
            'slug'       => 'default',
            'sections'   => $existing ?: '{}',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_presets');
    }
};
