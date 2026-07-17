<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('icon', 10)->nullable()->after('description');
            $table->enum('frequency', ['daily', 'weekly'])->default('daily')->after('action_type');
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropColumn(['icon', 'frequency']);
        });
    }
};
