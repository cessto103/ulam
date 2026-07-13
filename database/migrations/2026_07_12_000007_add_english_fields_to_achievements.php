<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// achievements.title/description have been Tagalog-only since launch;
// the app renders bilingual everywhere else, so add English counterparts
// (nullable — falls back to the Tagalog fields when unset) rather than
// swap which language the base columns hold.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });
    }
};
