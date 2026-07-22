<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Tindahan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMarkets();
        $this->seedMarketPrices();
        $this->call(TaskSeeder::class);
        $this->call(RewardTierSeeder::class);
        $this->seedRecipes();
        $this->call(UserSeeder::class);
        $this->call(CommunityPostSeeder::class);
        $this->call(StoreSeeder::class);
        $this->call(DemoStoreSeeder::class);
        $this->call(SellerPlanSeeder::class);
        $this->call(LegalDocumentSeeder::class);
        $this->call(ThemePresetSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(WeatherPhraseSeeder::class);
    }

    private function seedMarkets(): void
    {
        $markets = [
            [
                'name' => 'Cogeo Market',
                'type' => 'wet_market',
                'barangay' => 'Cogeo',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.6259,
                'longitude' => 121.1174,
                'is_active' => true,
            ],
            [
                'name' => 'Antipolo Public Market',
                'type' => 'wet_market',
                'barangay' => 'Poblacion',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.6259,
                'longitude' => 121.1760,
                'is_active' => true,
            ],
            [
                'name' => 'SM City Antipolo',
                'type' => 'supermarket',
                'barangay' => 'Dela Paz',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.5978,
                'longitude' => 121.1767,
                'is_active' => true,
            ],
            [
                'name' => 'Robinsons Place Antipolo',
                'type' => 'supermarket',
                'barangay' => 'San Roque',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'latitude' => 14.6166,
                'longitude' => 121.1658,
                'is_active' => true,
            ],
        ];

        foreach ($markets as $market) {
            Market::firstOrCreate(['name' => $market['name']], $market);
        }

        $cogeo = Market::where('name', 'Cogeo Market')->first();
        if ($cogeo) {
            $tindahanList = [
                ['name' => 'Aling Nena Fish Stall', 'type' => 'isda'],
                ['name' => 'Mang Ben Vegetables', 'type' => 'gulay'],
                ['name' => 'Kuya Rico Meat Shop', 'type' => 'karne'],
            ];

            foreach ($tindahanList as $t) {
                Tindahan::firstOrCreate(
                    ['name' => $t['name'], 'market_id' => $cogeo->id],
                    [
                        'market_id' => $cogeo->id,
                        'name' => $t['name'],
                        'type' => $t['type'],
                        'barangay' => $cogeo->barangay,
                        'municipality' => $cogeo->municipality,
                        'province' => $cogeo->province,
                        'region' => $cogeo->region,
                        'latitude' => $cogeo->latitude,
                        'longitude' => $cogeo->longitude,
                        'is_active' => true,
                        'is_verified' => true,
                    ]
                );
            }
        }
    }

    private function seedMarketPrices(): void
    {
        $cogeo     = Market::where('name', 'Cogeo Market')->first();
        $smAntipolo = Market::where('name', 'SM City Antipolo')->first();
        $robinsons = Market::where('name', 'Robinsons Place Antipolo')->first();

        $nena  = Tindahan::where('name', 'Aling Nena Fish Stall')->first();
        $ben   = Tindahan::where('name', 'Mang Ben Vegetables')->first();
        $rico  = Tindahan::where('name', 'Kuya Rico Meat Shop')->first();

        if (! $nena || ! $ben || ! $rico) {
            return; // Markets not seeded yet
        }

        $fishPrices = [
            ['item_name' => 'Galunggong',    'category' => 'isda',  'price_per_unit' => 180, 'unit' => 'kg'],
            ['item_name' => 'Tilapia',        'category' => 'isda',  'price_per_unit' => 120, 'unit' => 'kg'],
            ['item_name' => 'Bangus',         'category' => 'isda',  'price_per_unit' => 200, 'unit' => 'kg'],
            ['item_name' => 'Tunsoy',         'category' => 'isda',  'price_per_unit' => 90,  'unit' => 'kg'],
            ['item_name' => 'Dilis',          'category' => 'isda',  'price_per_unit' => 80,  'unit' => '100g'],
            ['item_name' => 'Tahong',         'category' => 'isda',  'price_per_unit' => 80,  'unit' => 'kg'],
            ['item_name' => 'Hipon (suahe)',  'category' => 'isda',  'price_per_unit' => 320, 'unit' => 'kg'],
            ['item_name' => 'Pusit',          'category' => 'isda',  'price_per_unit' => 280, 'unit' => 'kg'],
            ['item_name' => 'Talakitok',      'category' => 'isda',  'price_per_unit' => 250, 'unit' => 'kg'],
            ['item_name' => 'Lapu-lapu',      'category' => 'isda',  'price_per_unit' => 400, 'unit' => 'kg'],
            ['item_name' => 'Pampano',        'category' => 'isda',  'price_per_unit' => 350, 'unit' => 'kg'],
            ['item_name' => 'Alimango',       'category' => 'isda',  'price_per_unit' => 600, 'unit' => 'kg'],
        ];

        $vegPrices = [
            ['item_name' => 'Kamatis',        'category' => 'gulay', 'price_per_unit' => 50,  'unit' => 'kg'],
            ['item_name' => 'Sibuyas',        'category' => 'gulay', 'price_per_unit' => 80,  'unit' => 'kg'],
            ['item_name' => 'Bawang',         'category' => 'gulay', 'price_per_unit' => 150, 'unit' => 'kg'],
            ['item_name' => 'Luya',           'category' => 'gulay', 'price_per_unit' => 100, 'unit' => 'kg'],
            ['item_name' => 'Kangkong',       'category' => 'gulay', 'price_per_unit' => 20,  'unit' => 'bundle'],
            ['item_name' => 'Talong',         'category' => 'gulay', 'price_per_unit' => 40,  'unit' => 'kg'],
            ['item_name' => 'Ampalaya',       'category' => 'gulay', 'price_per_unit' => 60,  'unit' => 'kg'],
            ['item_name' => 'Sayote',         'category' => 'gulay', 'price_per_unit' => 35,  'unit' => 'kg'],
            ['item_name' => 'Pechay',         'category' => 'gulay', 'price_per_unit' => 25,  'unit' => 'bundle'],
            ['item_name' => 'Sitaw',          'category' => 'gulay', 'price_per_unit' => 40,  'unit' => 'bundle'],
            ['item_name' => 'Kalabasa',       'category' => 'gulay', 'price_per_unit' => 35,  'unit' => 'kg'],
            ['item_name' => 'Gabi',           'category' => 'gulay', 'price_per_unit' => 45,  'unit' => 'kg'],
            ['item_name' => 'Puso ng Saging', 'category' => 'gulay', 'price_per_unit' => 40,  'unit' => 'pcs'],
            ['item_name' => 'Labanos',        'category' => 'gulay', 'price_per_unit' => 30,  'unit' => 'kg'],
            ['item_name' => 'Patola',         'category' => 'gulay', 'price_per_unit' => 30,  'unit' => 'kg'],
            ['item_name' => 'Upo',            'category' => 'gulay', 'price_per_unit' => 25,  'unit' => 'kg'],
            ['item_name' => 'Malunggay',      'category' => 'gulay', 'price_per_unit' => 15,  'unit' => 'bundle'],
            ['item_name' => 'Sili (green)',   'category' => 'gulay', 'price_per_unit' => 80,  'unit' => '100g'],
            ['item_name' => 'Tokwa',          'category' => 'gulay', 'price_per_unit' => 20,  'unit' => 'pcs'],
            ['item_name' => 'Toge',           'category' => 'gulay', 'price_per_unit' => 15,  'unit' => '100g'],
        ];

        $meatPrices = [
            ['item_name' => 'Baboy Liempo',   'category' => 'karne', 'price_per_unit' => 270, 'unit' => 'kg'],
            ['item_name' => 'Baboy Tadyang',  'category' => 'karne', 'price_per_unit' => 280, 'unit' => 'kg'],
            ['item_name' => 'Baboy Kasim',    'category' => 'karne', 'price_per_unit' => 240, 'unit' => 'kg'],
            ['item_name' => 'Baboy Pigue',    'category' => 'karne', 'price_per_unit' => 250, 'unit' => 'kg'],
            ['item_name' => 'Manok (buo)',    'category' => 'karne', 'price_per_unit' => 180, 'unit' => 'kg'],
            ['item_name' => 'Manok Hita',     'category' => 'karne', 'price_per_unit' => 200, 'unit' => 'kg'],
            ['item_name' => 'Manok Dibdib',   'category' => 'karne', 'price_per_unit' => 220, 'unit' => 'kg'],
            ['item_name' => 'Manok Pakpak',   'category' => 'karne', 'price_per_unit' => 170, 'unit' => 'kg'],
            ['item_name' => 'Itlog ng Manok', 'category' => 'karne', 'price_per_unit' => 8,   'unit' => 'pcs'],
            ['item_name' => 'Itlog ng Pato',  'category' => 'karne', 'price_per_unit' => 14,  'unit' => 'pcs'],
            ['item_name' => 'Baka Giniling',  'category' => 'karne', 'price_per_unit' => 350, 'unit' => 'kg'],
            ['item_name' => 'Hotdog',         'category' => 'karne', 'price_per_unit' => 55,  'unit' => 'pack'],
        ];

        $supermarketPrices = [
            ['item_name' => 'Bigas (premium)', 'category' => 'bigas', 'price_per_unit' => 60,  'unit' => 'kg'],
            ['item_name' => 'Bigas (regular)', 'category' => 'bigas', 'price_per_unit' => 48,  'unit' => 'kg'],
            ['item_name' => 'Monggo',          'category' => 'bigas', 'price_per_unit' => 95,  'unit' => 'kg'],
            ['item_name' => 'Toyo',            'category' => 'sangkap', 'price_per_unit' => 75,  'unit' => 'bottle'],
            ['item_name' => 'Patis',           'category' => 'sangkap', 'price_per_unit' => 42,  'unit' => 'bottle'],
            ['item_name' => 'Suka',            'category' => 'sangkap', 'price_per_unit' => 48,  'unit' => 'bottle'],
            ['item_name' => 'Mantika',         'category' => 'sangkap', 'price_per_unit' => 115, 'unit' => 'bottle'],
            ['item_name' => 'Asukal',          'category' => 'sangkap', 'price_per_unit' => 75,  'unit' => 'kg'],
            ['item_name' => 'Asin',            'category' => 'sangkap', 'price_per_unit' => 18,  'unit' => 'pack'],
            ['item_name' => 'Gata (canned)',   'category' => 'sangkap', 'price_per_unit' => 48,  'unit' => 'pcs'],
            ['item_name' => 'Sardinas',        'category' => 'sangkap', 'price_per_unit' => 24,  'unit' => 'pcs'],
            ['item_name' => 'Corned Beef',     'category' => 'sangkap', 'price_per_unit' => 52,  'unit' => 'pcs'],
            ['item_name' => 'Itlog (12pcs)',   'category' => 'karne', 'price_per_unit' => 90,  'unit' => 'tray'],
            ['item_name' => 'Harina',          'category' => 'sangkap', 'price_per_unit' => 55,  'unit' => 'kg'],
        ];

        $tindahanSets = [
            [$nena->id, $cogeo?->id, $fishPrices],
            [$ben->id,  $cogeo?->id, $vegPrices],
            [$rico->id, $cogeo?->id, $meatPrices],
        ];

        foreach ($tindahanSets as [$tindahanId, $marketId, $items]) {
            foreach ($items as $item) {
                MarketPrice::firstOrCreate(
                    ['tindahan_id' => $tindahanId, 'item_name' => $item['item_name']],
                    [
                        'market_id'      => $marketId,
                        'category'       => $item['category'],
                        'price_per_unit' => $item['price_per_unit'],
                        'unit'           => $item['unit'],
                        'is_available'   => true,
                    ]
                );
            }
        }

        // Supermarket prices (no tindahan, attached to market directly)
        foreach ([$smAntipolo, $robinsons] as $market) {
            if (! $market) continue;
            foreach ($supermarketPrices as $item) {
                MarketPrice::firstOrCreate(
                    ['market_id' => $market->id, 'tindahan_id' => null, 'item_name' => $item['item_name']],
                    [
                        'category'       => $item['category'],
                        'price_per_unit' => $item['price_per_unit'],
                        'unit'           => $item['unit'],
                        'is_available'   => true,
                    ]
                );
            }
        }
    }

    private function seedRecipes(): void
    {
        $recipes = [
            // â”€â”€ â‚±100 tier â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'title'              => 'Ginisang Kangkong',
                'description'        => 'Simpleng gulay na luto sa bawang at toyo. Mabilis, masustansya, at napakamura.',
                'budget_tag'         => 'budget_100',
                'estimated_cost'     => 45.00,
                'servings'           => 4,
                'prep_time_minutes'  => 5,
                'cook_time_minutes'  => 8,
                'tags'               => ['gulay', 'mabilis', 'halal'],
                'steps'              => [
                    'Hugasan ang kangkong at putulin ang matigal na tangkay.',
                    'Igisa ang bawang sa mantika hanggang golden.',
                    'Ilagay ang kangkong at haluin nang mabilis.',
                    'Timplahan ng toyo, asin, at paminta. Huwag sobrang luto.',
                ],
                'tips'               => ['Huwag matagal lutuin para hindi malanta.', 'Pwede dagdagan ng hipon para mas masarap.'],
                'ingredients' => [
                    ['name' => 'Kangkong',    'quantity' => '2', 'unit' => 'bundle', 'estimated_price' => 30, 'sort_order' => 1],
                    ['name' => 'Bawang',      'quantity' => '4', 'unit' => 'sibuyas', 'estimated_price' => 5, 'sort_order' => 2],
                    ['name' => 'Toyo',        'quantity' => '2', 'unit' => 'tbsp',   'estimated_price' => 5, 'sort_order' => 3],
                    ['name' => 'Mantika',     'quantity' => '1', 'unit' => 'tbsp',   'estimated_price' => 5, 'sort_order' => 4],
                ],
            ],
            [
                'title'              => 'Sardinas sa Kamatis',
                'description'        => 'Klasikong budget ulam â€” lata ng sardinas na niluto sa kamatis at sibuyas. 15 minuto lang!',
                'budget_tag'         => 'budget_100',
                'estimated_cost'     => 70.00,
                'servings'           => 4,
                'prep_time_minutes'  => 3,
                'cook_time_minutes'  => 10,
                'tags'               => ['isda', 'mabilis', 'budget'],
                'steps'              => [
                    'Igisa ang sibuyas at kamatis sa mantika.',
                    'Ilagay ang sardinas kasama ang sarsa mula sa lata.',
                    'Haluin at hayaang kumulo ng 5 minuto.',
                    'Timplahan ng asin at paminta ayon sa panlasa.',
                ],
                'tips'               => ['Pwede dagdagan ng siling berde para may anghang.', 'Mas masarap kung may itlog na pinirito sa tabi.'],
                'ingredients' => [
                    ['name' => 'Sardinas (lata)',  'quantity' => '2',  'unit' => 'lata',    'estimated_price' => 48, 'sort_order' => 1],
                    ['name' => 'Kamatis',          'quantity' => '2',  'unit' => 'pcs',     'estimated_price' => 10, 'sort_order' => 2],
                    ['name' => 'Sibuyas',          'quantity' => '1',  'unit' => 'pcs',     'estimated_price' => 5,  'sort_order' => 3],
                    ['name' => 'Mantika',          'quantity' => '1',  'unit' => 'tbsp',    'estimated_price' => 5,  'sort_order' => 4],
                    ['name' => 'Asin, paminta',    'quantity' => 'ayon sa lasa', 'unit' => '',      'estimated_price' => 2,  'sort_order' => 5],
                ],
            ],
            [
                'title'              => 'Monggo Guisado',
                'description'        => 'Masustansyang monggo na may dilis at dahon ng malunggay. Filipino comfort food sa murang halaga.',
                'budget_tag'         => 'budget_100',
                'estimated_cost'     => 95.00,
                'servings'           => 4,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 30,
                'tags'               => ['gulay', 'sabaw', 'masustansya'],
                'steps'              => [
                    'Ibabad ang monggo ng 30 minuto, banlawan.',
                    'Pakuluan ang monggo hanggang malambot (20-25 min).',
                    'Sa ibang kawali, igisa ang bawang, sibuyas, at kamatis.',
                    'Ilagay ang nilutong monggo at dilis. Haluin.',
                    'Ilagay ang dahon ng malunggay at timplahan ng patis.',
                ],
                'tips'               => ['Pwede gumamit ng pressure cooker para mas mabilis.', 'Mas masarap kung may chicharon sa ibabaw.'],
                'ingredients' => [
                    ['name' => 'Monggo',         'quantity' => '250', 'unit' => 'g',      'estimated_price' => 24, 'sort_order' => 1],
                    ['name' => 'Dilis',          'quantity' => '50',  'unit' => 'g',      'estimated_price' => 20, 'sort_order' => 2],
                    ['name' => 'Dahon ng malunggay', 'quantity' => '1', 'unit' => 'bundle', 'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Kamatis',        'quantity' => '2',   'unit' => 'pcs',    'estimated_price' => 10, 'sort_order' => 4],
                    ['name' => 'Sibuyas',        'quantity' => '1',   'unit' => 'pcs',    'estimated_price' => 5,  'sort_order' => 5],
                    ['name' => 'Bawang',         'quantity' => '4',   'unit' => 'sibuyas', 'estimated_price' => 5, 'sort_order' => 6],
                    ['name' => 'Patis',          'quantity' => '2',   'unit' => 'tbsp',   'estimated_price' => 8,  'sort_order' => 7],
                    ['name' => 'Mantika',        'quantity' => '1',   'unit' => 'tbsp',   'estimated_price' => 8,  'sort_order' => 8],
                ],
            ],
            // â”€â”€ â‚±200 tier â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'title'              => 'Adobong Manok',
                'description'        => 'Piling piling ulam ng Pilipino. Manok sa toyo at suka â€” siguradong masarap at hindi mabibigo.',
                'budget_tag'         => 'budget_200',
                'estimated_cost'     => 185.00,
                'servings'           => 4,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 35,
                'tags'               => ['manok', 'sabaw', 'klasiko'],
                'steps'              => [
                    'Ihalo ang manok, toyo, suka, bawang, dahon ng laurel, at paminta. Ibabad ng 30 minuto.',
                    'Sa kawali, lutuin ang manok sa marinade sa katamtamang apoy.',
                    'Hayaang kumulo hanggang maging malapot ang sarsa.',
                    'Pwede i-brown ang manok sa dulo para mas masarap.',
                ],
                'tips'               => ['Mas masarap kung lutuin ng dalawang beses â€” lutuin muna, palamigin, saka muling lutuin.', 'Huwag magdagdag ng tubig para mas concentrated ang lasa.'],
                'ingredients' => [
                    ['name' => 'Manok (hita + pakpak)', 'quantity' => '500', 'unit' => 'g',   'estimated_price' => 120, 'sort_order' => 1],
                    ['name' => 'Toyo',                  'quantity' => '4',   'unit' => 'tbsp', 'estimated_price' => 15, 'sort_order' => 2],
                    ['name' => 'Suka',                  'quantity' => '4',   'unit' => 'tbsp', 'estimated_price' => 10, 'sort_order' => 3],
                    ['name' => 'Bawang',                'quantity' => '6',   'unit' => 'sibuyas', 'estimated_price' => 8, 'sort_order' => 4],
                    ['name' => 'Dahon ng laurel',       'quantity' => '3',   'unit' => 'pcs',  'estimated_price' => 5,  'sort_order' => 5],
                    ['name' => 'Pamintang buo',         'quantity' => '1',   'unit' => 'tsp',  'estimated_price' => 5,  'sort_order' => 6],
                    ['name' => 'Mantika',               'quantity' => '2',   'unit' => 'tbsp', 'estimated_price' => 10, 'sort_order' => 7],
                    ['name' => 'Asin',                  'quantity' => 'ayon sa lasa', 'unit' => '',   'estimated_price' => 2,  'sort_order' => 8],
                ],
            ],
            [
                'title'              => 'Tortang Talong',
                'description'        => 'Simpleng tortang talong na inihaw at binuhusan ng itlog. Masustansya at napakamura.',
                'budget_tag'         => 'budget_200',
                'estimated_cost'     => 120.00,
                'servings'           => 4,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 20,
                'tags'               => ['gulay', 'itlog', 'mabilis'],
                'steps'              => [
                    'Ihaw ang talong sa direktang apoy hanggang maluto (10 min). Palamig.',
                    'Balatan ang talong at pindutin nang dahan-dahan para maging patag.',
                    'Talunin ang mga itlog sa mangkok. Timplahan ng asin at paminta.',
                    'Ibabad ang talong sa itlog at iprito sa kawali hanggang golden.',
                ],
                'tips'               => ['Pwede lagyan ng giniling na karne para mas masustansya.', 'Mas maganda kung ihain kasama ang katsap.'],
                'ingredients' => [
                    ['name' => 'Talong (malaki)', 'quantity' => '4',  'unit' => 'pcs',  'estimated_price' => 60, 'sort_order' => 1],
                    ['name' => 'Itlog',           'quantity' => '4',  'unit' => 'pcs',  'estimated_price' => 32, 'sort_order' => 2],
                    ['name' => 'Mantika',         'quantity' => '3',  'unit' => 'tbsp', 'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Asin, paminta',   'quantity' => 'ayon sa lasa', 'unit' => '',   'estimated_price' => 3,  'sort_order' => 4],
                ],
            ],
            [
                'title'              => 'Sinigang na Bangus',
                'description'        => 'Maasim na sabaw ng bangus na may kamatis, sibuyas, at gulay. Pinakasikat na Filipino dish!',
                'budget_tag'         => 'budget_200',
                'estimated_cost'     => 195.00,
                'servings'           => 4,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 25,
                'tags'               => ['isda', 'sabaw', 'klasiko'],
                'steps'              => [
                    'Pakuluan ang tubig. Ilagay ang kamatis at sibuyas. Lutuin ng 5 minuto.',
                    'Ilagay ang bangus at tamarind mix. Hayaang kumulo ng 10 minuto.',
                    'Dagdagan ang mga gulay (labanos, kangkong, sitaw) at lutuin ng 5 minuto.',
                    'Timplahan ng patis at asin ayon sa panlasa.',
                ],
                'tips'               => ['Huwag sobrang luto ang bangus para hindi mawatak.', 'Pwede gumamit ng sampalok na sariwa para mas masarap.'],
                'ingredients' => [
                    ['name' => 'Bangus (malaki)',    'quantity' => '1',   'unit' => 'pcs',  'estimated_price' => 100, 'sort_order' => 1],
                    ['name' => 'Sinigang mix',       'quantity' => '1',   'unit' => 'sachet', 'estimated_price' => 12, 'sort_order' => 2],
                    ['name' => 'Kamatis',            'quantity' => '3',   'unit' => 'pcs',  'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Sibuyas',            'quantity' => '1',   'unit' => 'pcs',  'estimated_price' => 5,  'sort_order' => 4],
                    ['name' => 'Labanos',            'quantity' => '1',   'unit' => 'pcs',  'estimated_price' => 15, 'sort_order' => 5],
                    ['name' => 'Kangkong',           'quantity' => '1',   'unit' => 'bundle', 'estimated_price' => 15, 'sort_order' => 6],
                    ['name' => 'Patis',              'quantity' => '2',   'unit' => 'tbsp', 'estimated_price' => 8,  'sort_order' => 7],
                ],
            ],
            // â”€â”€ â‚±400 tier â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'title'              => 'Chicken Tinola',
                'description'        => 'Mainit na sabaw ng manok na may papaya at dahon ng sili. Perpekto sa malamig na panahon.',
                'budget_tag'         => 'budget_400',
                'estimated_cost'     => 280.00,
                'servings'           => 5,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 40,
                'tags'               => ['manok', 'sabaw', 'masustansya'],
                'steps'              => [
                    'Igisa ang luya at sibuyas sa mantika hanggang mabango.',
                    'Ilagay ang manok at haluin hanggang maputi ang kulay.',
                    'Dagdagan ng tubig at hayaang kumulo ng 20 minuto.',
                    'Ilagay ang papaya at lutuin ng 10 minuto.',
                    'Dagdagan ang dahon ng sili at patis. Haluin at ihain.',
                ],
                'tips'               => ['Gamitin ang batang manok para mas malambot.', 'Pwede palitan ng papaya ng sayote.'],
                'ingredients' => [
                    ['name' => 'Manok (buo, hiniwa)', 'quantity' => '750', 'unit' => 'g',      'estimated_price' => 160, 'sort_order' => 1],
                    ['name' => 'Papaya (hilaw)',       'quantity' => '1',   'unit' => 'pcs',    'estimated_price' => 40,  'sort_order' => 2],
                    ['name' => 'Luya',                 'quantity' => '30',  'unit' => 'g',      'estimated_price' => 10,  'sort_order' => 3],
                    ['name' => 'Sibuyas',              'quantity' => '2',   'unit' => 'pcs',    'estimated_price' => 10,  'sort_order' => 4],
                    ['name' => 'Dahon ng sili',        'quantity' => '1',   'unit' => 'cup',    'estimated_price' => 15,  'sort_order' => 5],
                    ['name' => 'Patis',                'quantity' => '2',   'unit' => 'tbsp',   'estimated_price' => 8,   'sort_order' => 6],
                    ['name' => 'Mantika',              'quantity' => '2',   'unit' => 'tbsp',   'estimated_price' => 15,  'sort_order' => 7],
                    ['name' => 'Tubig',                'quantity' => '6',   'unit' => 'cups',   'estimated_price' => 0,   'sort_order' => 8],
                ],
            ],
            [
                'title'              => 'Pinakbet',
                'description'        => 'Tradisyunal na Ilocano dish na puno ng gulay. Masustansya at paboritong ulam ng maraming Pilipino.',
                'budget_tag'         => 'budget_400',
                'estimated_cost'     => 320.00,
                'servings'           => 5,
                'prep_time_minutes'  => 15,
                'cook_time_minutes'  => 25,
                'tags'               => ['gulay', 'baboy', 'tradisyunal'],
                'steps'              => [
                    'Igisa ang bawang at sibuyas sa mantika.',
                    'Ilagay ang baboy at lutuin hanggang brown.',
                    'Dagdagan ang kamatis at igisa hanggang malambot.',
                    'Ilagay ang lahat ng gulay at bagoong. Haluin.',
                    'Takpan at lutuin sa mababang apoy ng 15-20 minuto.',
                ],
                'tips'               => ['Huwag masyadong haluin para hindi madurog ang gulay.', 'Mas masarap kung may bagoong Ilocano.'],
                'ingredients' => [
                    ['name' => 'Ampalaya',   'quantity' => '1',  'unit' => 'pcs',  'estimated_price' => 30,  'sort_order' => 1],
                    ['name' => 'Talong',     'quantity' => '2',  'unit' => 'pcs',  'estimated_price' => 20,  'sort_order' => 2],
                    ['name' => 'Kalabasa',   'quantity' => '200','unit' => 'g',    'estimated_price' => 20,  'sort_order' => 3],
                    ['name' => 'Sitaw',      'quantity' => '1',  'unit' => 'bundle','estimated_price' => 20, 'sort_order' => 4],
                    ['name' => 'Okra',       'quantity' => '100','unit' => 'g',    'estimated_price' => 20,  'sort_order' => 5],
                    ['name' => 'Baboy liempo','quantity' => '200','unit' => 'g',   'estimated_price' => 60,  'sort_order' => 6],
                    ['name' => 'Bagoong',    'quantity' => '3',  'unit' => 'tbsp', 'estimated_price' => 25,  'sort_order' => 7],
                    ['name' => 'Kamatis',    'quantity' => '2',  'unit' => 'pcs',  'estimated_price' => 10,  'sort_order' => 8],
                    ['name' => 'Bawang, sibuyas', 'quantity' => 'ayon sa lasa', 'unit' => '', 'estimated_price' => 15, 'sort_order' => 9],
                ],
            ],
            // â”€â”€ higher-cost dishes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'title'              => 'Kare-Kare',
                'description'        => 'Espesyal na ulam sa gabi ng pamilya â€” beef na may mani at gulay, ihain kasama ang bagoong.',
                'budget_tag'         => 'budget_600',
                'estimated_cost'     => 450.00,
                'servings'           => 6,
                'prep_time_minutes'  => 20,
                'cook_time_minutes'  => 90,
                'tags'               => ['baka', 'espesyal', 'tradisyunal'],
                'steps'              => [
                    'Pakuluan ang beef hanggang malambot (1 oras).',
                    'Igisa ang bawang at sibuyas. Ilagay ang beef at sabaw.',
                    'Dagdagan ang peanut butter at annatto water. Haluin.',
                    'Ilagay ang gulay (puso ng saging, talong, sitaw). Lutuin ng 10 minuto.',
                    'Timplahan at ihain kasama ang ginisang bagoong.',
                ],
                'tips'               => ['Mas masarap kung banana blossom ang gamitin.', 'Pwede gumamit ng food coloring bilang kapalit ng annatto.'],
                'ingredients' => [
                    ['name' => 'Beef (kalitiran)',    'quantity' => '500', 'unit' => 'g',    'estimated_price' => 175, 'sort_order' => 1],
                    ['name' => 'Peanut butter',       'quantity' => '4',   'unit' => 'tbsp', 'estimated_price' => 40,  'sort_order' => 2],
                    ['name' => 'Puso ng saging',      'quantity' => '1',   'unit' => 'pcs',  'estimated_price' => 40,  'sort_order' => 3],
                    ['name' => 'Talong',              'quantity' => '2',   'unit' => 'pcs',  'estimated_price' => 20,  'sort_order' => 4],
                    ['name' => 'Sitaw',               'quantity' => '1',   'unit' => 'bundle','estimated_price' => 20, 'sort_order' => 5],
                    ['name' => 'Bagoong (ginisa)',    'quantity' => '4',   'unit' => 'tbsp', 'estimated_price' => 40,  'sort_order' => 6],
                    ['name' => 'Annatto / Atsuete',  'quantity' => '1',   'unit' => 'tbsp', 'estimated_price' => 10,  'sort_order' => 7],
                    ['name' => 'Bawang, sibuyas',     'quantity' => 'ayon sa lasa', 'unit' => '',   'estimated_price' => 15,  'sort_order' => 8],
                ],
            ],
            // ── Batch 2: recipes #82–100 ─────────────────────────────────────────────
            [
                'title'             => 'Ginisang Patola',
                'description'       => 'Patola na ginisa sa bawang at toyo — mabilis at pang-araw-araw.',
                'category'          => 'gulay',
                'budget_tag'        => 'budget_100',
                'estimated_cost'    => 65.00,
                'servings'          => 4,
                'prep_time_minutes' => 5,
                'cook_time_minutes' => 10,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&q=80',
                'tags'              => ['no-meat', 'vegetarian', 'gulay'],
                'steps'             => [
                    'Balatan at hiwain ang patola.',
                    'Igisa ang bawang sa mantika.',
                    'Ilagay ang patola at haluin.',
                    'Timplahan ng toyo at asin.',
                    'Lutuin ng 5–7 minuto.',
                    'Ihain nang mainit.',
                ],
                'tips' => ['Huwag sobrang luto para hindi malanta ang patola.', 'Pwede dagdagan ng giniling na karne.'],
                'ingredients' => [
                    ['name' => 'Patola',   'quantity' => '2 piraso, hiniwa', 'unit' => '',         'estimated_price' => 25, 'sort_order' => 1],
                    ['name' => 'Bawang',   'quantity' => '4 butil',          'unit' => '',         'estimated_price' => 5,  'sort_order' => 2],
                    ['name' => 'Toyo',     'quantity' => '2',                'unit' => 'kutsara',  'estimated_price' => 10, 'sort_order' => 3],
                    ['name' => 'Mantika',  'quantity' => '2',                'unit' => 'kutsara',  'estimated_price' => 8,  'sort_order' => 4],
                    ['name' => 'Asin',     'quantity' => 'ayon sa panlasa',  'unit' => '',         'estimated_price' => 5,  'sort_order' => 5],
                ],
            ],
            [
                'title'             => 'Batchoy',
                'description'       => 'Ilonggo-style na sabaw na may pancit at baboy — mainit at masustansya.',
                'category'          => 'pancit',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 280.00,
                'servings'          => 5,
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 30,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=400&q=80',
                'tags'              => ['no-seafood', 'baboy', 'pancit'],
                'steps'             => [
                    'Pakuluin ang sabaw ng baka o baboy.',
                    'Igisa ang bawang, sibuyas, at luya.',
                    'Ilagay ang ginisa sa sabaw.',
                    'Ilagay ang miki o bihon.',
                    'Ilagay ang atay at bituka ng baboy.',
                    'Timplahan ng patis.',
                    'Ihain na may chicharron at dahon ng sibuyas.',
                ],
                'tips' => ['Mas masarap ang sariwang miki kaysa bihon.', 'Pwede lagyan ng hard-boiled na itlog.'],
                'ingredients' => [
                    ['name' => 'Baboy (laman at atay)', 'quantity' => '¼ kilo',      'unit' => '',        'estimated_price' => 90, 'sort_order' => 1],
                    ['name' => 'Miki o bihon',          'quantity' => '250',          'unit' => 'gramo',   'estimated_price' => 40, 'sort_order' => 2],
                    ['name' => 'Sabaw ng baboy',         'quantity' => '6',            'unit' => 'tasa',    'estimated_price' => 20, 'sort_order' => 3],
                    ['name' => 'Chicharron',             'quantity' => '¼',            'unit' => 'tasa',    'estimated_price' => 30, 'sort_order' => 4],
                    ['name' => 'Bawang, sibuyas, luya', 'quantity' => 'katamtaman',   'unit' => '',        'estimated_price' => 15, 'sort_order' => 5],
                    ['name' => 'Patis',                  'quantity' => '2',            'unit' => 'kutsara', 'estimated_price' => 10, 'sort_order' => 6],
                    ['name' => 'Dahon ng sibuyas',       'quantity' => 'katamtaman',   'unit' => '',        'estimated_price' => 8,  'sort_order' => 7],
                ],
            ],
            [
                'title'             => 'Tinolang Hipon',
                'description'       => 'Tinola-style na sabaw na may hipon sa halip na manok — espesyal na twist.',
                'category'          => 'sabaw',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 230.00,
                'servings'          => 4,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 20,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&q=80',
                'tags'              => ['no-pork', 'no-meat', 'seafood', 'sabaw'],
                'steps'             => [
                    'Igisa ang luya, bawang, at sibuyas.',
                    'Ilagay ang hipon at lutuin ng 2 minuto.',
                    'Dagdagan ng tubig at pakuluin.',
                    'Ilagay ang papaya o sayote.',
                    'Timplahan ng patis.',
                    'Ilagay ang dahon ng sili at i-off ang apoy.',
                ],
                'tips' => ['Huwag sobrang luto ang hipon para hindi maging rubber.', 'Pwede palitan ng papaya ng sayote.'],
                'ingredients' => [
                    ['name' => 'Hipon',              'quantity' => '½ kilo',       'unit' => '',        'estimated_price' => 160, 'sort_order' => 1],
                    ['name' => 'Papaya (hilaw)',      'quantity' => '¼ piraso',     'unit' => '',        'estimated_price' => 15,  'sort_order' => 2],
                    ['name' => 'Dahon ng sili',       'quantity' => '1',            'unit' => 'tasa',    'estimated_price' => 10,  'sort_order' => 3],
                    ['name' => 'Luya',                'quantity' => '1 thumb-size', 'unit' => '',        'estimated_price' => 5,   'sort_order' => 4],
                    ['name' => 'Bawang at sibuyas',   'quantity' => 'katamtaman',   'unit' => '',        'estimated_price' => 13,  'sort_order' => 5],
                    ['name' => 'Patis',               'quantity' => '2',            'unit' => 'kutsara', 'estimated_price' => 10,  'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Ginisang Gulay na Halo-Halo',
                'description'       => 'Mixed vegetables na ginisa — pinaka-masustansyang ulam.',
                'category'          => 'gulay',
                'budget_tag'        => 'budget_100',
                'estimated_cost'    => 130.00,
                'servings'          => 5,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 15,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&q=80',
                'tags'              => ['no-meat', 'vegetarian', 'gulay'],
                'steps'             => [
                    'Hugasan at hiwain ang lahat ng gulay.',
                    'Igisa ang bawang at sibuyas.',
                    'Ilagay ang mga matigás na gulay muna.',
                    'Sundan ang mga malambot.',
                    'Timplahan ng toyo at oyster sauce.',
                    'Haluin at lutuin ng 5 minuto pa.',
                ],
                'tips' => ['Huwag overcook ang gulay para manatiling crisp.', 'Pwede lagyan ng tofu para mas masustansya.'],
                'ingredients' => [
                    ['name' => 'Broccoli',           'quantity' => '½ piraso',   'unit' => '',        'estimated_price' => 30, 'sort_order' => 1],
                    ['name' => 'Carrots',             'quantity' => '2 piraso',   'unit' => '',        'estimated_price' => 20, 'sort_order' => 2],
                    ['name' => 'Repolyo',             'quantity' => '¼ ulo',      'unit' => '',        'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Sitaw',               'quantity' => '½ bundle',   'unit' => '',        'estimated_price' => 15, 'sort_order' => 4],
                    ['name' => 'Toyo',                'quantity' => '2',          'unit' => 'kutsara', 'estimated_price' => 10, 'sort_order' => 5],
                    ['name' => 'Oyster sauce',        'quantity' => '1',          'unit' => 'kutsara', 'estimated_price' => 15, 'sort_order' => 6],
                    ['name' => 'Bawang at sibuyas',   'quantity' => 'katamtaman', 'unit' => '',        'estimated_price' => 13, 'sort_order' => 7],
                ],
            ],
            [
                'title'             => 'Chicken Adobo Flakes',
                'description'       => 'Tuyong adobong manok na may sibuyas — masarap na almusal.',
                'category'          => 'almusal',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 175.00,
                'servings'          => 4,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 45,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=400&q=80',
                'tags'              => ['no-pork', 'no-seafood', 'manok', 'almusal'],
                'steps'             => [
                    'Lutuin ang manok sa adobo style.',
                    'Alisin ang buto at himayin ang karne.',
                    'Iprito sa mantika hanggang malutong at tuyong-tuyo.',
                    'Igisa ang sibuyang singsing.',
                    'Ihalo at ihain na may sinangag at pritong itlog.',
                ],
                'tips' => ['Mas masarap kung lutuin hanggang talagang tuyo at crispy.', 'Pwede i-store sa ref ng hanggang 3 araw.'],
                'ingredients' => [
                    ['name' => 'Manok (adobo)', 'quantity' => '½ kilo',        'unit' => '',        'estimated_price' => 110, 'sort_order' => 1],
                    ['name' => 'Toyo',           'quantity' => '3',             'unit' => 'kutsara', 'estimated_price' => 10,  'sort_order' => 2],
                    ['name' => 'Suka',           'quantity' => '3',             'unit' => 'kutsara', 'estimated_price' => 8,   'sort_order' => 3],
                    ['name' => 'Bawang',         'quantity' => '5 butil',       'unit' => '',        'estimated_price' => 5,   'sort_order' => 4],
                    ['name' => 'Sibuyas',        'quantity' => '2 piraso, singsing', 'unit' => '',   'estimated_price' => 15,  'sort_order' => 5],
                    ['name' => 'Mantika',        'quantity' => '3',             'unit' => 'kutsara', 'estimated_price' => 12,  'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Sarciadong Isda',
                'description'       => 'Pritong isda sa tomato-egg sarsa — masarap at mabilis lutuin.',
                'category'          => 'isda',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 155.00,
                'servings'          => 4,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 20,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400&q=80',
                'tags'              => ['no-pork', 'no-meat', 'isda'],
                'steps'             => [
                    'Iprito ang isda hanggang ginto — itabi.',
                    'Igisa ang bawang at sibuyas sa natirang mantika.',
                    'Ilagay ang kamatis at lutuin.',
                    'Batiin ang itlog at ibuhos.',
                    'Ilagay ang isda at haluin nang maingat.',
                    'Timplahan at ihain.',
                ],
                'tips' => ['Gamitin ang buong isda para mas masarap ang lasa.', 'Pwede gumamit ng tilapia, bangus, o galunggong.'],
                'ingredients' => [
                    ['name' => 'Tilapia o bangus',  'quantity' => '2 piraso',   'unit' => '',        'estimated_price' => 90, 'sort_order' => 1],
                    ['name' => 'Itlog',              'quantity' => '2 piraso',   'unit' => '',        'estimated_price' => 22, 'sort_order' => 2],
                    ['name' => 'Kamatis',            'quantity' => '3 piraso',   'unit' => '',        'estimated_price' => 20, 'sort_order' => 3],
                    ['name' => 'Bawang at sibuyas',  'quantity' => 'katamtaman', 'unit' => '',        'estimated_price' => 13, 'sort_order' => 4],
                    ['name' => 'Mantika',            'quantity' => '3',          'unit' => 'kutsara', 'estimated_price' => 12, 'sort_order' => 5],
                ],
            ],
            [
                'title'             => 'Kilawing Kambing',
                'description'       => 'Kambing na niluto sa suka at pampalasa — Ilocos-style na espesyalidad.',
                'category'          => 'karne',
                'budget_tag'        => 'budget_400',
                'estimated_cost'    => 450.00,
                'servings'          => 6,
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 60,
                'difficulty'        => 'mahirap',
                'image_url'         => 'https://images.unsplash.com/photo-1544025162-d76594e8cef1?w=400&q=80',
                'tags'              => ['no-pork', 'no-seafood', 'karne'],
                'steps'             => [
                    'Hugasan ang kambing at ibabad sa suka ng 30 minuto.',
                    'Pakuluin ng 45 minuto hanggang malambot.',
                    'Igisa ang bawang, sibuyas, at luya.',
                    'Ilagay ang kambing.',
                    'Ibuhos ang suka at toyo.',
                    'Ilagay ang sili at lutuin ng 10 minuto.',
                    'Timplahan at ihain.',
                ],
                'tips' => ['Ibabad ng matagal para mawala ang amoy ng kambing.', 'Mas masarap kung ibabad sa suka at luya magdamag.'],
                'ingredients' => [
                    ['name' => 'Kambing (tinadtad)', 'quantity' => '½ kilo',      'unit' => '',        'estimated_price' => 250, 'sort_order' => 1],
                    ['name' => 'Suka',               'quantity' => '½',           'unit' => 'tasa',    'estimated_price' => 15,  'sort_order' => 2],
                    ['name' => 'Toyo',               'quantity' => '3',           'unit' => 'kutsara', 'estimated_price' => 12,  'sort_order' => 3],
                    ['name' => 'Siling haba',        'quantity' => '5 piraso',    'unit' => '',        'estimated_price' => 15,  'sort_order' => 4],
                    ['name' => 'Bawang',             'quantity' => '6 butil',     'unit' => '',        'estimated_price' => 8,   'sort_order' => 5],
                    ['name' => 'Sibuyas',            'quantity' => '2 piraso',    'unit' => '',        'estimated_price' => 15,  'sort_order' => 6],
                    ['name' => 'Luya',               'quantity' => '2 thumb-size','unit' => '',        'estimated_price' => 8,   'sort_order' => 7],
                ],
            ],
            [
                'title'             => 'Pork Ribs BBQ',
                'description'       => 'Inihaw na tadyang ng baboy sa homemade BBQ sauce — pampistahan.',
                'category'          => 'baboy',
                'budget_tag'        => 'budget_600',
                'estimated_cost'    => 560.00,
                'servings'          => 6,
                'prep_time_minutes' => 60,
                'cook_time_minutes' => 90,
                'difficulty'        => 'mahirap',
                'image_url'         => 'https://images.unsplash.com/photo-1544025162-d76594e8cef1?w=400&q=80',
                'tags'              => ['no-seafood', 'baboy', 'inihaw'],
                'steps'             => [
                    'I-marinate ang tadyang sa toyo, kalamansi, asukal, at bawang ng 2 oras.',
                    'Ilagay sa oven ng 1 oras sa 150°C.',
                    'Ilabas at i-baste ng BBQ sauce.',
                    'Itaas ang temperatura sa 200°C.',
                    'Lutuin ng 30 minuto pa.',
                    'Ihain na may coleslaw.',
                ],
                'tips' => ['I-marinate ng mas matagal para mas malambot at maalat.', 'Pwede ihaw sa grill kaysa oven para mas mapausok.'],
                'ingredients' => [
                    ['name' => 'Tadyang ng baboy', 'quantity' => '1 kilo',   'unit' => '',        'estimated_price' => 380, 'sort_order' => 1],
                    ['name' => 'Toyo',             'quantity' => '¼',        'unit' => 'tasa',    'estimated_price' => 15,  'sort_order' => 2],
                    ['name' => 'Kalamansi',        'quantity' => '8 piraso', 'unit' => '',        'estimated_price' => 20,  'sort_order' => 3],
                    ['name' => 'Asukal pula',      'quantity' => '3',        'unit' => 'kutsara', 'estimated_price' => 10,  'sort_order' => 4],
                    ['name' => 'BBQ sauce',        'quantity' => '½',        'unit' => 'tasa',    'estimated_price' => 50,  'sort_order' => 5],
                    ['name' => 'Bawang',           'quantity' => '6 butil',  'unit' => '',        'estimated_price' => 8,   'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Laing',
                'description'       => 'Taro leaves sa gata na may baboy o hipon — sikat na Bicolano dish.',
                'category'          => 'gulay',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 220.00,
                'servings'          => 6,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 45,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&q=80',
                'tags'              => ['gulay', 'gata', 'bicol'],
                'steps'             => [
                    'Ilagay ang tuyo na dahon ng gabi sa kawali.',
                    'Ibuhos ang gata.',
                    'Ilagay ang baboy o hipon.',
                    'Ilagay ang sili at bawang.',
                    'Huwag haluin ng maaga.',
                    'Lutuin sa mahinang apoy ng 45 minuto.',
                    'Timplahan at ihain.',
                ],
                'tips' => ['Huwag haluin agad para hindi mangangati ang laing.', 'Mas masarap kung may hipon na dried (payo) ang laman.'],
                'ingredients' => [
                    ['name' => 'Dahon ng gabi (tuyo)', 'quantity' => '2',          'unit' => 'tasa',    'estimated_price' => 30, 'sort_order' => 1],
                    ['name' => 'Gata',                  'quantity' => '2',          'unit' => 'tasa',    'estimated_price' => 60, 'sort_order' => 2],
                    ['name' => 'Baboy o hipon',          'quantity' => '¼ kilo',    'unit' => '',        'estimated_price' => 80, 'sort_order' => 3],
                    ['name' => 'Siling labuyo',          'quantity' => '4 piraso',  'unit' => '',        'estimated_price' => 10, 'sort_order' => 4],
                    ['name' => 'Bawang',                 'quantity' => '4 butil',   'unit' => '',        'estimated_price' => 5,  'sort_order' => 5],
                    ['name' => 'Bagoong',                'quantity' => '1',         'unit' => 'kutsara', 'estimated_price' => 10, 'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Pork Estofado',
                'description'       => 'Baboy sa toyo at saging na saba — matamis at maasim na ulam.',
                'category'          => 'baboy',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 270.00,
                'servings'          => 5,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 45,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1544025162-d76594e8cef1?w=400&q=80',
                'tags'              => ['no-seafood', 'baboy'],
                'steps'             => [
                    'Igisa ang bawang at sibuyas.',
                    'Ilagay ang baboy at lutuin.',
                    'Ibuhos ang suka at toyo.',
                    'Ilagay ang saging na saba at asukal.',
                    'Lutuin ng 30 minuto sa mahinang apoy.',
                    'Timplahan at ihain.',
                ],
                'tips' => ['Ang saging na saba ang nagbibigay ng tamis — huwag palitan ng saging na hilaw.', 'Pwede dagdagan ng patatas.'],
                'ingredients' => [
                    ['name' => 'Baboy (liempo)',    'quantity' => '½ kilo',           'unit' => '',        'estimated_price' => 150, 'sort_order' => 1],
                    ['name' => 'Saging na saba',    'quantity' => '4 piraso, hiniwa', 'unit' => '',        'estimated_price' => 30,  'sort_order' => 2],
                    ['name' => 'Toyo',              'quantity' => '¼',               'unit' => 'tasa',    'estimated_price' => 12,  'sort_order' => 3],
                    ['name' => 'Suka',              'quantity' => '¼',               'unit' => 'tasa',    'estimated_price' => 8,   'sort_order' => 4],
                    ['name' => 'Asukal',            'quantity' => '2',               'unit' => 'kutsara', 'estimated_price' => 8,   'sort_order' => 5],
                    ['name' => 'Bawang at sibuyas', 'quantity' => 'katamtaman',      'unit' => '',        'estimated_price' => 13,  'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Monggo at Chicharon',
                'description'       => 'Monggo na may crunchy chicharon sa ibabaw — paboritong combo.',
                'category'          => 'sabaw',
                'budget_tag'        => 'budget_100',
                'estimated_cost'    => 130.00,
                'servings'          => 5,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 30,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&q=80',
                'tags'              => ['sabaw', 'monggo'],
                'steps'             => [
                    'Lutuin ang monggo ng 20 minuto.',
                    'Igisa ang bawang, sibuyas, at kamatis.',
                    'Ilagay ang ginisa sa monggo.',
                    'Timplahan ng patis.',
                    'Ilagay ang ampalaya.',
                    'Ihain na may chicharon sa ibabaw.',
                ],
                'tips' => ['Mas masarap kung may ampalaya — nagbibigay ng konting pait.', 'Pwede gumamit ng canned monggo para mas mabilis.'],
                'ingredients' => [
                    ['name' => 'Monggo',            'quantity' => '½ kilo',     'unit' => '',        'estimated_price' => 40, 'sort_order' => 1],
                    ['name' => 'Chicharon',          'quantity' => '½',          'unit' => 'tasa',    'estimated_price' => 40, 'sort_order' => 2],
                    ['name' => 'Ampalaya',           'quantity' => '1 piraso',   'unit' => '',        'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Kamatis',            'quantity' => '2 piraso',   'unit' => '',        'estimated_price' => 15, 'sort_order' => 4],
                    ['name' => 'Bawang at sibuyas',  'quantity' => 'katamtaman', 'unit' => '',        'estimated_price' => 13, 'sort_order' => 5],
                    ['name' => 'Patis',              'quantity' => '2',          'unit' => 'kutsara', 'estimated_price' => 7,  'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Inihaw na Pusit (Simple)',
                'description'       => 'Simpleng inihaw na pusit — mabilis at masarap na seafood.',
                'category'          => 'isda',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 200.00,
                'servings'          => 3,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 10,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400&q=80',
                'tags'              => ['no-pork', 'no-meat', 'seafood', 'inihaw'],
                'steps'             => [
                    'Linisin ang pusit.',
                    'I-marinate sa toyo at kalamansi ng 15 minuto.',
                    'Ihaw ng 3–4 minuto bawat gilid.',
                    'Hiwain at ihain na may dip na toyo at suka.',
                ],
                'tips' => ['Huwag sobrang luto para hindi maging matigas ang pusit.', 'Pwede lagyan ng asin sa loob ng pusit bago ihaw.'],
                'ingredients' => [
                    ['name' => 'Pusit',   'quantity' => '½ kilo',          'unit' => '',        'estimated_price' => 150, 'sort_order' => 1],
                    ['name' => 'Toyo',    'quantity' => '3',               'unit' => 'kutsara', 'estimated_price' => 12,  'sort_order' => 2],
                    ['name' => 'Kalamansi','quantity' => '4 piraso',       'unit' => '',        'estimated_price' => 10,  'sort_order' => 3],
                    ['name' => 'Suka',    'quantity' => 'para sa sawsawan','unit' => '',        'estimated_price' => 8,   'sort_order' => 4],
                ],
            ],
            [
                'title'             => 'Ukoy',
                'description'       => 'Crispy na hipon at toge fritter — paboritong panluto at merienda.',
                'category'          => 'meryenda',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 160.00,
                'servings'          => 5,
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 20,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=400&q=80',
                'tags'              => ['no-pork', 'seafood', 'meryenda'],
                'steps'             => [
                    'Ihalo ang harina, mais na harina, itlog, at tubig.',
                    'Ilagay ang hipon, toge, at dahon ng sibuyas.',
                    'Timplahan ng asin at paminta.',
                    'Kumiha ng isang kaserola ng batter at iprito.',
                    'Lutuin ng 3 minuto bawat gilid.',
                    'Ihain na may suka at bawang dip.',
                ],
                'tips' => ['Gamitin ang malamig na tubig para mas crispy.', 'Mag-ihanda ng sawsawan na suka at bawang.'],
                'ingredients' => [
                    ['name' => 'Hipon (maliit)', 'quantity' => '¼ kilo',           'unit' => '',        'estimated_price' => 70, 'sort_order' => 1],
                    ['name' => 'Toge',           'quantity' => '1',                 'unit' => 'tasa',    'estimated_price' => 15, 'sort_order' => 2],
                    ['name' => 'Harina',         'quantity' => '1',                 'unit' => 'tasa',    'estimated_price' => 15, 'sort_order' => 3],
                    ['name' => 'Mais na harina', 'quantity' => '¼',                'unit' => 'tasa',    'estimated_price' => 8,  'sort_order' => 4],
                    ['name' => 'Itlog',          'quantity' => '2 piraso',          'unit' => '',        'estimated_price' => 22, 'sort_order' => 5],
                    ['name' => 'Mantika',        'quantity' => '1',                 'unit' => 'tasa',    'estimated_price' => 35, 'sort_order' => 6],
                    ['name' => 'Suka',           'quantity' => 'para sa sawsawan',  'unit' => '',        'estimated_price' => 8,  'sort_order' => 7],
                ],
            ],
            [
                'title'             => 'Nilupak',
                'description'       => 'Pinagsamang kamote at niyog — tradisyunal na Pilipinong merienda.',
                'category'          => 'meryenda',
                'budget_tag'        => 'budget_100',
                'estimated_cost'    => 70.00,
                'servings'          => 6,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 20,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=400&q=80',
                'tags'              => ['no-meat', 'no-seafood', 'vegetarian', 'meryenda', 'dessert'],
                'steps'             => [
                    'Pakuluin ang kamote hanggang malambot.',
                    'Balatan at durugin habang mainit.',
                    'Ihalo ang gata at asukal.',
                    'Durugin ng mabuti hanggang makinis.',
                    'Ilagay sa plato at lagyan ng margarine.',
                    'Ihain bilang merienda.',
                ],
                'tips' => ['Gamitin ang orange na kamote para mas matamis.', 'Pwede lagyan ng grated coconut sa ibabaw.'],
                'ingredients' => [
                    ['name' => 'Kamote',    'quantity' => '½ kilo',   'unit' => '',        'estimated_price' => 30, 'sort_order' => 1],
                    ['name' => 'Gata',      'quantity' => '½',        'unit' => 'tasa',    'estimated_price' => 20, 'sort_order' => 2],
                    ['name' => 'Asukal',    'quantity' => '2',        'unit' => 'kutsara', 'estimated_price' => 8,  'sort_order' => 3],
                    ['name' => 'Margarine', 'quantity' => '1',        'unit' => 'kutsara', 'estimated_price' => 8,  'sort_order' => 4],
                ],
            ],
            [
                'title'             => 'Pork BBQ Skewers',
                'description'       => 'Inihaw na baboy sa stick — klasikong Pilipinong street food.',
                'category'          => 'baboy',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 250.00,
                'servings'          => 5,
                'prep_time_minutes' => 60,
                'cook_time_minutes' => 20,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1544025162-d76594e8cef1?w=400&q=80',
                'tags'              => ['no-seafood', 'baboy', 'inihaw'],
                'steps'             => [
                    'Hiwain ang baboy ng manipis.',
                    'I-marinate sa toyo, kalamansi, bawang, at asukal ng 1 oras.',
                    'Tusukan sa bamboo skewer.',
                    'Ihaw ng 4–5 minuto bawat gilid.',
                    'I-baste ng sarsa habang inihahaw.',
                    'Ihain na mainit.',
                ],
                'tips' => ['I-soak ang bamboo skewer sa tubig ng 30 minuto para hindi masunog.', 'Mas masarap kung may banana ketchup na sarsa.'],
                'ingredients' => [
                    ['name' => 'Baboy (liempo o kasim)', 'quantity' => '½ kilo',   'unit' => '',        'estimated_price' => 140, 'sort_order' => 1],
                    ['name' => 'Toyo',                   'quantity' => '¼',        'unit' => 'tasa',    'estimated_price' => 12,  'sort_order' => 2],
                    ['name' => 'Kalamansi',              'quantity' => '6 piraso', 'unit' => '',        'estimated_price' => 15,  'sort_order' => 3],
                    ['name' => 'Asukal pula',            'quantity' => '2',        'unit' => 'kutsara', 'estimated_price' => 8,   'sort_order' => 4],
                    ['name' => 'Banana ketchup',         'quantity' => '3',        'unit' => 'kutsara', 'estimated_price' => 15,  'sort_order' => 5],
                    ['name' => 'Bawang',                 'quantity' => '4 butil',  'unit' => '',        'estimated_price' => 5,   'sort_order' => 6],
                ],
            ],
            [
                'title'             => 'Ginataang Bilo-Bilo',
                'description'       => 'Matamis na gata na may bilo-bilo at saging — paboritong Pilipinong dessert.',
                'category'          => 'dessert',
                'budget_tag'        => 'budget_100',
                'estimated_cost'    => 110.00,
                'servings'          => 6,
                'prep_time_minutes' => 20,
                'cook_time_minutes' => 20,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=400&q=80',
                'tags'              => ['no-meat', 'no-seafood', 'vegetarian', 'dessert', 'gata'],
                'steps'             => [
                    'Gumawa ng bilo-bilo: ihalo ang malagkit na harina at tubig.',
                    'Gawing maliit na bola.',
                    'Pakuluin ang gata at tubig.',
                    'Ilagay ang bilo-bilo hanggang lumutang.',
                    'Ilagay ang saging at kamote.',
                    'Lagyan ng asukal at jackfruit.',
                    'Ihain mainit o malamig.',
                ],
                'tips' => ['Mas masarap kung may nata de coco.', 'Huwag masyadong palakihin ang bilo-bilo para mas mabilis maluto.'],
                'ingredients' => [
                    ['name' => 'Malagkit na harina', 'quantity' => '1',          'unit' => 'tasa',    'estimated_price' => 25, 'sort_order' => 1],
                    ['name' => 'Gata',               'quantity' => '2',          'unit' => 'tasa',    'estimated_price' => 55, 'sort_order' => 2],
                    ['name' => 'Saging na saba',     'quantity' => '3 piraso, hiniwa', 'unit' => '', 'estimated_price' => 20, 'sort_order' => 3],
                    ['name' => 'Asukal',             'quantity' => '¼',          'unit' => 'tasa',    'estimated_price' => 10, 'sort_order' => 4],
                    ['name' => 'Langka',             'quantity' => '½',          'unit' => 'tasa',    'estimated_price' => 15, 'sort_order' => 5],
                ],
            ],
            [
                'title'             => 'Pork and Mushroom Adobo',
                'description'       => 'Adobo na may mushroom — mas mabango at espesyal na bersyon.',
                'category'          => 'baboy',
                'budget_tag'        => 'budget_200',
                'estimated_cost'    => 230.00,
                'servings'          => 5,
                'prep_time_minutes' => 10,
                'cook_time_minutes' => 35,
                'difficulty'        => 'madali',
                'image_url'         => 'https://images.unsplash.com/photo-1544025162-d76594e8cef1?w=400&q=80',
                'tags'              => ['no-seafood', 'baboy', 'adobo'],
                'steps'             => [
                    'I-marinate ang baboy sa toyo, suka, at bawang.',
                    'Lutuin ang baboy sa marinade ng 20 minuto.',
                    'Ilagay ang mushroom.',
                    'Lutuin ng 10 minuto pa.',
                    'Bawasan ang apoy at hayaang lumapot ang sarsa.',
                    'Ihain nang mainit.',
                ],
                'tips' => ['Gamitin ang button mushroom o shiitake para mas mabango.', 'Pwede lagyan ng patatas para mas maraming serving.'],
                'ingredients' => [
                    ['name' => 'Baboy (kasim)',  'quantity' => '½ kilo',   'unit' => '',        'estimated_price' => 140, 'sort_order' => 1],
                    ['name' => 'Mushroom',       'quantity' => '1 tasa, hiniwa', 'unit' => '', 'estimated_price' => 40,  'sort_order' => 2],
                    ['name' => 'Toyo',           'quantity' => '¼',        'unit' => 'tasa',    'estimated_price' => 12,  'sort_order' => 3],
                    ['name' => 'Suka',           'quantity' => '¼',        'unit' => 'tasa',    'estimated_price' => 8,   'sort_order' => 4],
                    ['name' => 'Bawang',         'quantity' => '6 butil',  'unit' => '',        'estimated_price' => 8,   'sort_order' => 5],
                    ['name' => 'Bay leaves',     'quantity' => '2 piraso', 'unit' => '',        'estimated_price' => 5,   'sort_order' => 6],
                    ['name' => 'Mantika',        'quantity' => '2',        'unit' => 'kutsara', 'estimated_price' => 8,   'sort_order' => 7],
                ],
            ],
            [
                'title'             => 'Chicken Afritada Espesyal',
                'description'       => 'Premium na afritada na may olibo at green peas — pang-handaan.',
                'category'          => 'manok',
                'budget_tag'        => 'budget_400',
                'estimated_cost'    => 380.00,
                'servings'          => 6,
                'prep_time_minutes' => 15,
                'cook_time_minutes' => 40,
                'difficulty'        => 'katamtaman',
                'image_url'         => 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=400&q=80',
                'tags'              => ['no-pork', 'no-seafood', 'manok', 'espesyal'],
                'steps'             => [
                    'Igisa ang bawang at sibuyas.',
                    'Ilagay ang manok at lutuin hanggang ginto.',
                    'Ibuhos ang tomato sauce at sabaw.',
                    'Ilagay ang patatas, carrots, at bell pepper.',
                    'Lutuin ng 20 minuto.',
                    'Ilagay ang olibo at green peas.',
                    'Timplahan at ihain.',
                ],
                'tips' => ['Lagyan ng sugar ng kaunti para mas balanced ang lasa.', 'Mas maganda kung may red at yellow bell pepper.'],
                'ingredients' => [
                    ['name' => 'Manok (buong piraso)',         'quantity' => '1.5 kilo',  'unit' => '',        'estimated_price' => 200, 'sort_order' => 1],
                    ['name' => 'Tomato sauce',                 'quantity' => '1.5',       'unit' => 'lata',    'estimated_price' => 40,  'sort_order' => 2],
                    ['name' => 'Patatas',                      'quantity' => '3 piraso',  'unit' => '',        'estimated_price' => 30,  'sort_order' => 3],
                    ['name' => 'Carrots',                      'quantity' => '2 piraso',  'unit' => '',        'estimated_price' => 20,  'sort_order' => 4],
                    ['name' => 'Bell pepper (pula at dilaw)',  'quantity' => '2 piraso',  'unit' => '',        'estimated_price' => 30,  'sort_order' => 5],
                    ['name' => 'Green olives',                 'quantity' => '¼',         'unit' => 'tasa',    'estimated_price' => 25,  'sort_order' => 6],
                    ['name' => 'Green peas',                   'quantity' => '½',         'unit' => 'tasa',    'estimated_price' => 20,  'sort_order' => 7],
                    ['name' => 'Bawang at sibuyas',            'quantity' => 'katamtaman','unit' => '',        'estimated_price' => 13,  'sort_order' => 8],
                ],
            ],
            // ── Lechon Kawali (existing, kept here for reference) ──────────────────
            [
                'title'              => 'Lechon Kawali',
                'description'        => 'Crispy na lechon kawali na kaya mong gawin sa bahay. Para sa espesyal na okasyon o simpleng salu-salo.',
                'budget_tag'         => 'budget_400',
                'estimated_cost'     => 380.00,
                'servings'           => 6,
                'prep_time_minutes'  => 10,
                'cook_time_minutes'  => 60,
                'tags'               => ['baboy', 'espesyal', 'prito'],
                'steps'              => [
                    'Pakuluan ang liempo na may asin, bawang, at dahon ng laurel ng 45 minuto.',
                    'Palamigin ang liempo. Tuyo nang mabuti.',
                    'Iprito sa mainit na mantika hanggang golden at crispy (15-20 min).',
                    'Hiwain at ihain kasama ang lechon sauce o vinegar dip.',
                ],
                'tips'               => ['Mas mainam kung palamigin sa ref bago iprito para mas crispy.', 'Gumamit ng deep pot para hindi mag-splatter ang mantika.'],
                'ingredients' => [
                    ['name' => 'Baboy liempo',      'quantity' => '750', 'unit' => 'g',    'estimated_price' => 210, 'sort_order' => 1],
                    ['name' => 'Asin',              'quantity' => '2',   'unit' => 'tbsp', 'estimated_price' => 5,   'sort_order' => 2],
                    ['name' => 'Bawang',            'quantity' => '6',   'unit' => 'sibuyas','estimated_price' => 8,  'sort_order' => 3],
                    ['name' => 'Dahon ng laurel',   'quantity' => '3',   'unit' => 'pcs',  'estimated_price' => 5,   'sort_order' => 4],
                    ['name' => 'Mantika (prito)',   'quantity' => '2',   'unit' => 'cups', 'estimated_price' => 60,  'sort_order' => 5],
                    ['name' => 'Lechon sauce',      'quantity' => '1',   'unit' => 'bottle','estimated_price' => 45, 'sort_order' => 6],
                ],
            ],
        ];

        foreach ($recipes as $r) {
            $ingredients = $r['ingredients'];
            unset($r['ingredients']);

            $recipe = \App\Models\Recipe::updateOrCreate(
                ['title' => $r['title']],
                array_merge($r, [
                    'source'          => 'official',
                    'is_published'    => true,
                    'is_premium_only' => false,
                ])
            );

            // Sync ingredients — delete old, re-insert fresh
            \App\Models\RecipeIngredient::where('recipe_id', $recipe->id)->delete();
            foreach ($ingredients as $ing) {
                \App\Models\RecipeIngredient::create(array_merge($ing, ['recipe_id' => $recipe->id]));
            }
        }
    }
}

