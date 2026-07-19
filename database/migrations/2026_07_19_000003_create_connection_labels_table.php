<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seeded here (not in DatabaseSeeder) so both fresh installs and the
        // live DB get the defaults from the migration alone.
        DB::table('connection_labels')->insert([
            ['name' => 'Household', 'sort_order' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Relative',  'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Normal',    'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_labels');
    }
};
