<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            // Relationship labels are per-side and private: each party only
            // ever reads/writes their own column, the other side never sees
            // it. The status enum is left untouched — 'connected' doubles as
            // "accepted" (avoids an enum ALTER on a live table).
            $table->foreignId('requester_label_id')->nullable()->after('status')
                ->constrained('connection_labels')->nullOnDelete();
            $table->foreignId('recipient_label_id')->nullable()->after('requester_label_id')
                ->constrained('connection_labels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requester_label_id');
            $table->dropConstrainedForeignId('recipient_label_id');
        });
    }
};
