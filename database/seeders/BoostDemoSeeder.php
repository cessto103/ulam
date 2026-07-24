<?php

namespace Database\Seeders;

use App\Models\AdBoost;
use App\Models\Recipe;
use App\Models\Tindahan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Demo data only -- gives the "Recommended for You" (recipes) and
// "Recommended Stores" / "Recommended Stores Near You" sections something
// real to show in local dev without needing to actually buy boosts through
// the PayMongo/reward-credit flow. Safe to re-run: firstOrCreate keys each
// AdBoost on (boostable, status=active), so reseeding never doubles them up.
class BoostDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->boostRecipes();
        $this->boostStores();
    }

    private function boostRecipes(): void
    {
        $recipes = Recipe::whereNotNull('user_id')
            ->where('is_published', true)
            ->inRandomOrder()
            ->limit(7)
            ->get();

        if ($recipes->isEmpty()) {
            $this->command->warn('BoostDemoSeeder: no community recipes found to boost -- run CommunityRecipeSeeder first.');
            return;
        }

        foreach ($recipes as $recipe) {
            $this->boost($recipe, $recipe->user_id);
        }

        $this->command->info("BoostDemoSeeder: boosted {$recipes->count()} recipes.");
    }

    private function boostStores(): void
    {
        // Cogeo Market's coordinates -- jittered nearby so these stores
        // realistically fall inside a 5km "near you" search in local dev.
        $baseLat = 14.6259;
        $baseLng = 121.1174;

        $demoOwners = [
            ['name' => 'Rosa Villanueva', 'username' => 'rosa_boost_demo', 'email' => 'rosa.boostdemo@ulam.app'],
            ['name' => 'Dado Santos',     'username' => 'dado_boost_demo', 'email' => 'dado.boostdemo@ulam.app'],
            ['name' => 'Ligaya Cruz',     'username' => 'ligaya_boost_demo', 'email' => 'ligaya.boostdemo@ulam.app'],
            ['name' => 'Boy Fernandez',   'username' => 'boy_boost_demo', 'email' => 'boy.boostdemo@ulam.app'],
            ['name' => 'Nanay Cording',   'username' => 'cording_boost_demo', 'email' => 'cording.boostdemo@ulam.app'],
            ['name' => 'Ka Tonyo',        'username' => 'tonyo_boost_demo', 'email' => 'tonyo.boostdemo@ulam.app'],
            ['name' => 'Baby Reyes',      'username' => 'baby_boost_demo', 'email' => 'baby.boostdemo@ulam.app'],
        ];

        $demoStores = [
            ['name' => "Rosa's Gulayan",        'type' => 'gulay',   'barangay' => 'Cogeo'],
            ['name' => 'Dado Poultry Supply',    'type' => 'karne',   'barangay' => 'Dela Paz'],
            ['name' => "Ligaya's Sari-Sari",     'type' => 'tindahan','barangay' => 'San Roque'],
            ['name' => 'Boy Fresh Fish Corner',  'type' => 'isda',    'barangay' => 'Mayamot'],
            ['name' => "Nanay Cording's Store",  'type' => 'tindahan','barangay' => 'Dalig'],
            ['name' => "Ka Tonyo's Rice Store",  'type' => 'bigas',   'barangay' => 'San Jose'],
            ['name' => "Baby's Grocery Nook",    'type' => 'grocery', 'barangay' => 'Mambugan'],
        ];

        foreach ($demoOwners as $i => $data) {
            $owner = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'username' => $data['username'], 'password' => Hash::make('password123')],
            );

            $jitterLat = $baseLat + (mt_rand(-300, 300) / 10000); // ~±3.3km max
            $jitterLng = $baseLng + (mt_rand(-300, 300) / 10000);

            Tindahan::firstOrCreate(
                ['user_id' => $owner->id, 'market_id' => null, 'name' => $demoStores[$i]['name']],
                [
                    'type' => $demoStores[$i]['type'],
                    'barangay' => $demoStores[$i]['barangay'],
                    'municipality' => 'Antipolo City',
                    'province' => 'Rizal',
                    'region' => 'IV-A',
                    'latitude' => $jitterLat,
                    'longitude' => $jitterLng,
                    'is_active' => true,
                    'is_verified' => false,
                ],
            );
        }

        // Boost every eligible user-owned store -- the ones just created above,
        // plus any real ones already seeded elsewhere (StoreSeeder, DemoStoreSeeder) --
        // capped at 7 total.
        $stores = Tindahan::whereNotNull('user_id')
            ->where('is_active', true)
            ->where('hidden_by_plan', false)
            ->inRandomOrder()
            ->limit(7)
            ->get();

        foreach ($stores as $store) {
            $this->boost($store, $store->user_id);
        }

        $this->command->info("BoostDemoSeeder: boosted {$stores->count()} stores.");
    }

    private function boost(Recipe|Tindahan $boostable, int $ownerId): void
    {
        $durationDays = [3, 7, 14][array_rand([3, 7, 14])];

        AdBoost::firstOrCreate(
            [
                'boostable_type' => get_class($boostable),
                'boostable_id'   => $boostable->id,
                'status'         => 'active',
            ],
            [
                'user_id'          => $ownerId,
                'duration_days'    => $durationDays,
                'amount_paid'      => 0,
                'payment_method'   => 'seed_demo',
                'payment_reference' => null,
                'starts_at'        => now(),
                'expires_at'       => now()->addDays($durationDays),
                'reviewed_at'      => now(),
            ],
        );
    }
}
