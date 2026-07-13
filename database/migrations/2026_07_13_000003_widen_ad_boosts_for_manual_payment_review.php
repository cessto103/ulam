<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // duration enum predates the flexible boost_options.duration_days catalog —
        // widen status the same way ad_subscriptions was widened, and add the
        // manual-GCash review fields boosts never had.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_boosts MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        Schema::table('ad_boosts', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_days')->nullable()->after('duration');
            $table->string('payment_method', 20)->default('gcash_manual')->after('amount_paid');
            $table->string('payment_reference')->nullable()->unique()->after('payment_method');
            $table->string('rejected_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejected_reason');
            $table->foreignId('activated_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ad_boosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activated_by');
            $table->dropColumn([
                'duration_days',
                'payment_method',
                'payment_reference',
                'rejected_reason',
                'reviewed_at',
            ]);
        });
    }
};
