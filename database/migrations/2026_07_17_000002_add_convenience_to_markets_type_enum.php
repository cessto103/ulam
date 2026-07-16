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
            throw new RuntimeException("Unsupported driver [{$driver}] for markets type migration.");
        }

        // MarketDiscoveryService::mapType() has always returned 'convenience' for
        // OSM shop=convenience POIs (7-Eleven, Ministop, etc.), but the enum never
        // allowed it — every convenience-store discovery silently threw and aborted
        // the rest of that batch (swallowed by the caller's catch block).
        DB::statement("ALTER TABLE markets MODIFY COLUMN type ENUM('wet_market','palengke','supermarket','grocery','tindahan','convenience') NOT NULL DEFAULT 'wet_market'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Unsupported driver [{$driver}] for markets type rollback.");
        }

        DB::table('markets')
            ->where('type', 'convenience')
            ->update(['type' => 'grocery']);

        DB::statement("ALTER TABLE markets MODIFY COLUMN type ENUM('wet_market','palengke','supermarket','grocery','tindahan') NOT NULL DEFAULT 'wet_market'");
    }
};
