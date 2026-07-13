<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Refunds are recorded as negative ledger rows, so `amount` must be signed.
    // `plan_type` gains room for seller plan types ("seller:negosyante:15d" > 20 chars).
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY amount INT NOT NULL');
            DB::statement('ALTER TABLE payments MODIFY plan_type VARCHAR(40) NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY amount INT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE payments MODIFY plan_type VARCHAR(20) NOT NULL');
        }
    }
};
