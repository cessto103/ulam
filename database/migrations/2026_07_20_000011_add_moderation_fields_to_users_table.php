<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('restricted_until')->nullable()->after('ban_reason');
        });

        // ban_reason was VARCHAR(255), but Admin\UserController::ban()'s own
        // validation already allowed max:500 -- a latent truncation bug, fixed
        // here to actually match what the app has always claimed to accept.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY ban_reason VARCHAR(500) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('restricted_until');
        });
    }
};
