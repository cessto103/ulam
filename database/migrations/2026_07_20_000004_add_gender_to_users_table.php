<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optional, skippable everywhere it's collected. Null means
            // unset (never asked, or the user skipped it) -- there's no
            // separate "prefer not to say" value, since nothing in the app
            // needs to distinguish "declined" from "never asked".
            $table->enum('gender', ['male', 'female'])->nullable()->after('household_size');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
