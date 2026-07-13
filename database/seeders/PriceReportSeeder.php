<?php

namespace Database\Seeders;

use App\Models\CommunityPriceReport;
use App\Models\Tindahan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds pending community price reports against "ITOY Sari Sari Store"
 * so the owner can exercise the accept/decline review flow.
 *
 * Run: php artisan db:seed --class=PriceReportSeeder
 */
class PriceReportSeeder extends Seeder
{
    public function run(): void
    {
        $store = Tindahan::where('name', 'like', '%ITOY%')->first();

        if (! $store) {
            $this->command->warn('No store matching "ITOY" found — create the store first.');
            return;
        }

        $reporters = User::where('id', '!=', $store->user_id)
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($reporters->isEmpty()) {
            $this->command->warn('No other users found to act as reporters.');
            return;
        }

        $items = [
            ['item_name' => 'Itlog (pula)',        'category' => 'itlog',    'reported_price' => 12.00,  'unit' => 'pcs'],
            ['item_name' => 'Kamatis',             'category' => 'gulay',    'reported_price' => 85.00,  'unit' => 'kg'],
            ['item_name' => 'Sibuyas (red)',       'category' => 'gulay',    'reported_price' => 140.00, 'unit' => 'kg'],
            ['item_name' => 'Ligo Sardines',       'category' => 'delata',   'reported_price' => 26.50,  'unit' => 'lata'],
            ['item_name' => 'Lucky Me Pancit Canton', 'category' => 'pansit', 'reported_price' => 15.00, 'unit' => 'pcs'],
            ['item_name' => 'Kape (3-in-1)',       'category' => 'kape',     'reported_price' => 9.00,   'unit' => 'sachet'],
            ['item_name' => 'Mantika (bote)',      'category' => 'mantika',  'reported_price' => 95.00,  'unit' => 'bottle'],
            ['item_name' => 'Asin (fine)',         'category' => 'pampalasa','reported_price' => 18.00,  'unit' => 'pack'],
            ['item_name' => 'Toyo (Silver Swan)',  'category' => 'sangkap',  'reported_price' => 32.00,  'unit' => 'bottle'],
            ['item_name' => 'Galunggong',          'category' => 'isda',     'reported_price' => 220.00, 'unit' => 'kg'],
            ['item_name' => 'Manok (whole)',       'category' => 'manok',    'reported_price' => 195.00, 'unit' => 'kg'],
            ['item_name' => 'Saging (lakatan)',    'category' => 'prutas',   'reported_price' => 90.00,  'unit' => 'kg'],
        ];

        $count = 0;
        foreach ($items as $i => $item) {
            $reporter = $reporters[$i % $reporters->count()];
            CommunityPriceReport::create([
                ...$item,
                'user_id'      => $reporter->id,
                'tindahan_id'  => $store->id,
                'market_id'    => $store->market_id,
                'barangay'     => $store->barangay,
                'municipality' => $store->municipality,
                'province'     => $reporter->province,
                'status'       => 'pending',
                'created_at'   => now()->subHours(rand(1, 96)),
            ]);
            $count++;
        }

        $this->command->info("Seeded {$count} pending price reports for \"{$store->name}\".");
    }
}
