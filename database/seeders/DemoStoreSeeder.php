<?php

namespace Database\Seeders;

use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Models\User;
use Illuminate\Database\Seeder;

// Seeds a store owned by a different (demo) account than the main test user,
// so store-ownership display (e.g. "Listed by ...", "My Stores" list) can be
// checked against a second, independent profile.
class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'eddie.demo@ulam.app')->first();

        if (! $owner) {
            $this->command->warn('DemoStoreSeeder: owner account (eddie.demo@ulam.app) not found, skipping.');
            return;
        }

        $store = Tindahan::firstOrCreate(
            ['user_id' => $owner->id, 'market_id' => null, 'name' => "Mang Eddie's Meat Shop"],
            [
                'description' => 'Sariwang karne at manok, tuwing umaga galing sa supplier.',
                'type' => 'karne',
                'barangay' => 'Mayamot',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.6132,
                'longitude' => 121.1699,
                'contact_number' => '+639171234567',
                'store_hours' => [
                    'monday'    => ['closed' => false, 'open' => '05:00', 'close' => '14:00'],
                    'tuesday'   => ['closed' => false, 'open' => '05:00', 'close' => '14:00'],
                    'wednesday' => ['closed' => false, 'open' => '05:00', 'close' => '14:00'],
                    'thursday'  => ['closed' => false, 'open' => '05:00', 'close' => '14:00'],
                    'friday'    => ['closed' => false, 'open' => '05:00', 'close' => '14:00'],
                    'saturday'  => ['closed' => false, 'open' => '05:00', 'close' => '15:00'],
                    'sunday'    => ['closed' => true],
                ],
                'is_active' => true,
                'is_verified' => true,
            ]
        );

        $items = [
            ['item_name' => 'Baboy Liempo',    'category' => 'karne', 'price_per_unit' => 275, 'unit' => 'kg'],
            ['item_name' => 'Baboy Kasim',     'category' => 'karne', 'price_per_unit' => 235, 'unit' => 'kg'],
            ['item_name' => 'Baboy Tadyang',   'category' => 'karne', 'price_per_unit' => 285, 'unit' => 'kg'],
            ['item_name' => 'Manok (buo)',     'category' => 'karne', 'price_per_unit' => 185, 'unit' => 'kg'],
            ['item_name' => 'Manok Hita',      'category' => 'karne', 'price_per_unit' => 205, 'unit' => 'kg'],
            ['item_name' => 'Manok Pakpak',    'category' => 'karne', 'price_per_unit' => 175, 'unit' => 'kg'],
            ['item_name' => 'Baka Giniling',   'category' => 'karne', 'price_per_unit' => 345, 'unit' => 'kg'],
            ['item_name' => 'Itlog ng Manok',  'category' => 'karne', 'price_per_unit' => 8,   'unit' => 'pcs'],
            ['item_name' => 'Longganisa',      'category' => 'karne', 'price_per_unit' => 165, 'unit' => 'kg'],
            ['item_name' => 'Tocino',          'category' => 'karne', 'price_per_unit' => 175, 'unit' => 'kg'],
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
            "DemoStoreSeeder done. \"{$store->name}\" (owned by {$owner->name}) now has "
            . MarketPrice::where('tindahan_id', $store->id)->count() . ' items.'
        );
    }
}
