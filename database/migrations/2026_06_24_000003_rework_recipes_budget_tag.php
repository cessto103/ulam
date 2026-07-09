<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite stores enum columns as text already, so there is nothing to rewrite.
        if ($driver === 'sqlite') {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Unsupported driver [{$driver}] for recipes budget_tag migration.");
        }

        DB::statement("ALTER TABLE recipes MODIFY COLUMN budget_tag VARCHAR(30) NOT NULL DEFAULT 'budget_200'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Unsupported driver [{$driver}] for recipes budget_tag rollback.");
        }

        DB::table('recipes')
            ->whereNotIn('budget_tag', ['budget_100', 'budget_200', 'budget_400', 'budget_400plus'])
            ->update(['budget_tag' => 'budget_400plus']);

        DB::statement("ALTER TABLE recipes MODIFY COLUMN budget_tag ENUM('budget_100','budget_200','budget_400','budget_400plus') NOT NULL DEFAULT 'budget_200'");
    }
};
