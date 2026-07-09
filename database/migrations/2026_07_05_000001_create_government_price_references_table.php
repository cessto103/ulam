<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_price_references', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30); // 'da_bantay_presyo' | 'dti_srp'
            $table->string('item_name', 100);
            $table->string('category', 50)->nullable();
            $table->decimal('price_min', 8, 2);
            $table->decimal('price_max', 8, 2);
            $table->string('unit', 30);
            $table->string('region', 50); // matches users.region, or 'National'
            $table->date('bulletin_date')->nullable();
            $table->string('source_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['source', 'item_name', 'region'], 'gov_price_source_item_region_unique');
            $table->index(['item_name', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_price_references');
    }
};
