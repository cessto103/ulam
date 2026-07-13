<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tindahan', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('description');
            $table->string('cover_photo')->nullable()->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('tindahan', function (Blueprint $table) {
            $table->dropColumn(['photo', 'cover_photo']);
        });
    }
};
