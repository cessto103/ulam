<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('collage_style', 20)->default('gradient')->after('is_published');
            $table->string('gradient_key', 10)->default('grad_a')->after('collage_style');
            $table->json('image_urls')->nullable()->after('gradient_key');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['collage_style', 'gradient_key', 'image_urls']);
        });
    }
};
