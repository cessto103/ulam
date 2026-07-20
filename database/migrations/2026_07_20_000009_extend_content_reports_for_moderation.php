<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_reports', function (Blueprint $table) {
            $table->foreignId('reported_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
        });

        // Reconcile status vocabulary with ListingReport's (pending|actioned|dismissed)
        // -- ContentReport shipped with open|reviewed|dismissed, but 'reviewed' was
        // never actually used anywhere (no admin surface existed to set it), so this
        // is a safe one-way rename, not a real data-loss risk.
        DB::table('content_reports')->where('status', 'open')->update(['status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE content_reports MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('content_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_user_id');
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn('resolved_at');
        });
    }
};
