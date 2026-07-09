<?php

namespace Database\Seeders;

use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'cessto103@gmail.com')->first();

        if (! $owner) {
            $this->command->warn('StoreSeeder: owner account (cessto103@gmail.com) not found, skipping.');
            return;
        }

        $store = Tindahan::firstOrCreate(
            ['user_id' => $owner->id, 'market_id' => null, 'name' => 'ITOY Sari Sari Store'],
            [
                'description' => 'Selling gulay, Karne at ib pa',
                'type' => 'tindahan',
                'barangay' => 'Dela Paz',
                'municipality' => 'Antipolo',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.621506,
                'longitude' => 121.1813621,
                'contact_number' => '+639679206396',
                'is_active' => true,
                'is_verified' => false,
            ]
        );

        if (! $store->type) {
            $store->update(['type' => 'tindahan']);
        }

        $items = [
            // gulay
            ['item_name' => 'Kamatis',          'category' => 'gulay',   'price_per_unit' => 55,  'unit' => 'kg'],
            ['item_name' => 'Sibuyas',          'category' => 'gulay',   'price_per_unit' => 85,  'unit' => 'kg'],
            ['item_name' => 'Bawang',           'category' => 'gulay',   'price_per_unit' => 160, 'unit' => 'kg'],
            ['item_name' => 'Kalabasa',         'category' => 'gulay',   'price_per_unit' => 40,  'unit' => 'kg'],
            ['item_name' => 'Talong',           'category' => 'gulay',   'price_per_unit' => 45,  'unit' => 'kg'],
            ['item_name' => 'Sitaw',            'category' => 'gulay',   'price_per_unit' => 42,  'unit' => 'bundle'],
            // karne
            ['item_name' => 'Baboy Liempo',     'category' => 'karne',   'price_per_unit' => 280, 'unit' => 'kg'],
            ['item_name' => 'Manok (buo)',      'category' => 'karne',   'price_per_unit' => 190, 'unit' => 'kg'],
            ['item_name' => 'Itlog ng Manok',   'category' => 'karne',   'price_per_unit' => 9,   'unit' => 'pcs'],
            // sangkap / sari-sari staples
            ['item_name' => 'Toyo',             'category' => 'sangkap', 'price_per_unit' => 20,  'unit' => 'sachet'],
            ['item_name' => 'Suka',             'category' => 'sangkap', 'price_per_unit' => 18,  'unit' => 'sachet'],
            ['item_name' => 'Bigas (regular)',  'category' => 'bigas',   'price_per_unit' => 50,  'unit' => 'kg'],
            ['item_name' => 'Asukal',           'category' => 'sangkap', 'price_per_unit' => 78,  'unit' => 'kg'],
            ['item_name' => 'Sardinas',         'category' => 'sangkap', 'price_per_unit' => 25,  'unit' => 'pcs'],
            ['item_name' => 'Instant Noodles',  'category' => 'sangkap', 'price_per_unit' => 15,  'unit' => 'pack'],
        ];

        foreach ($items as $item) {
            MarketPrice::firstOrCreate(
                ['tindahan_id' => $store->id, 'item_name' => $item['item_name']],
                [
                    'market_id'      => null,
                    'category'       => $item['category'],
                    'price_per_unit' => $item['price_per_unit'],
                    'unit'           => $item['unit'],
                    'is_available'   => true,
                ]
            );
        }

        $this->command->info(
            "StoreSeeder done. \"{$store->name}\" now has "
            . MarketPrice::where('tindahan_id', $store->id)->count() . ' items.'
        );
    }
}
