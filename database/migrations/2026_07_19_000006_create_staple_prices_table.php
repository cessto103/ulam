<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tingi/takal prices for staples and condiments: recipes cost these
        // proportionally (2 tbsp of soy sauce = a few pesos), but nobody can
        // BUY 2 tbsp — the smallest purchasable unit is a sachet/takal. When
        // a shopping list is generated and an ingredient name matches a row
        // here, the list line uses this price/unit instead, with the recipe's
        // original amount kept as a note. Meal plan cost estimates are never
        // touched. Admin-managed under Prices & Markets.
        Schema::create('staple_prices', function (Blueprint $table) {
            $table->id();
            $table->string('item_name')->unique();
            $table->string('unit', 30); // sachet, takal, pack...
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('staple_prices')->insert([
            ['item_name' => 'Toyo',     'unit' => 'sachet', 'price' => 10.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['item_name' => 'Suka',     'unit' => 'sachet', 'price' => 10.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['item_name' => 'Asin',     'unit' => 'takal',  'price' => 5.00,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['item_name' => 'Paminta',  'unit' => 'sachet', 'price' => 8.00,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['item_name' => 'Mantika',  'unit' => 'sachet', 'price' => 15.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['item_name' => 'Asukal',   'unit' => 'takal',  'price' => 10.00, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('staple_prices');
    }
};
