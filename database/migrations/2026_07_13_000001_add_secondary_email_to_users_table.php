<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('secondary_email')->nullable()->after('email');
            $table->timestamp('secondary_email_verified_at')->nullable()->after('secondary_email');
            $table->string('secondary_email_otp')->nullable()->after('secondary_email_verified_at');
            $table->timestamp('secondary_email_otp_expires_at')->nullable()->after('secondary_email_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_email',
                'secondary_email_verified_at',
                'secondary_email_otp',
                'secondary_email_otp_expires_at',
            ]);
        });
    }
};
