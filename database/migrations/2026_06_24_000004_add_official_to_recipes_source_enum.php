<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Unsupported driver [{$driver}] for recipes source migration.");
        }

        DB::statement("ALTER TABLE recipes MODIFY COLUMN source ENUM('ai_generated','community','admin','official') NOT NULL DEFAULT 'community'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Unsupported driver [{$driver}] for recipes source rollback.");
        }

        DB::table('recipes')
            ->where('source', 'official')
            ->update(['source' => 'community']);

        DB::statement("ALTER TABLE recipes MODIFY COLUMN source ENUM('ai_generated','community','admin') NOT NULL DEFAULT 'community'");
    }
};
