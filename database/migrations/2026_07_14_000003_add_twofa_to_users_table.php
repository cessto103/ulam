<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // TOTP (Google Authenticator) — admin dashboard only for now.
            $table->text('twofa_secret')->nullable()->after('password_reset_otp_expires_at');
            $table->timestamp('twofa_enabled_at')->nullable()->after('twofa_secret');
            // Timestamp counter of the last accepted code — blocks replay within the window.
            $table->unsignedBigInteger('twofa_last_ts')->nullable()->after('twofa_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['twofa_secret', 'twofa_enabled_at', 'twofa_last_ts']);
        });
    }
};
