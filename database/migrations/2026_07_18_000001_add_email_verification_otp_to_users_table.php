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
            // Same in-app OTP pattern as password reset / secondary-email
            // verification — a 6-digit emailed code, not a clickable link.
            $table->string('email_verification_otp')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_otp_expires_at')->nullable()->after('email_verification_otp');
        });

        // Everyone who registered before this feature shipped never went
        // through a verification step — grandfather them in as verified so
        // this migration doesn't lock existing accounts out on next login.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verification_otp', 'email_verification_otp_expires_at']);
        });
    }
};
