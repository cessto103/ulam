<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Every login/register token has always been created with a hardcoded
// literal name ('mobile') and zero device metadata -- these four nullable
// columns are what makes a token identifiable as "this specific device" for
// the login-activity/devices feature. Populated going forward only by
// AuthController::login()/register(); existing tokens stay null until their
// next fresh login (no retroactive backfill is possible).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_name', 150)->nullable()->after('abilities');
            $table->string('platform', 20)->nullable()->after('device_name');
            $table->string('app_version', 20)->nullable()->after('platform');
            $table->string('ip_address', 45)->nullable()->after('app_version');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'platform', 'app_version', 'ip_address']);
        });
    }
};
