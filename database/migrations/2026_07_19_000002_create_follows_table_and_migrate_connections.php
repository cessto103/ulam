<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followed_id']);
            $table->index('followed_id');
        });

        // Every existing `connections` row was created by the old follow()
        // endpoint as a one-way auto-accepted follow — the table was never
        // used for actual mutual connections. Move them all to `follows`,
        // then clear `connections` so it can serve its real purpose
        // (request/accept mutual connections) from an empty slate.
        //
        // The clear is intentionally irreversible (down() only drops
        // `follows`): dump the connections table before deploying this to
        // the live server. INSERT IGNORE makes an accidental re-run safe.
        DB::transaction(function () {
            DB::statement(
                'INSERT IGNORE INTO follows (follower_id, followed_id, created_at, updated_at)
                 SELECT requester_id, recipient_id, created_at, updated_at FROM connections'
            );
            DB::table('connections')->delete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
