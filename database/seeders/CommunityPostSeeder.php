<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunityPostSeeder extends Seeder
{
    private const MY_USER_ID = 14;

    private const POST_BODIES = [
        'price_tip' => [
            'Nakita ko sa Puregold ngayon — asukal ₱58/kilo, mas mura kesa sa palengke!',
            'Sa Savemore sa amin, sibuyas ₱25 isang kilo. Sulit na sulit!',
            'Mga ate/kuya, ang itlog sa Aling Nena ₱6.50 lang per piece. Mura pero sariwa pa rin!',
            'Update: kamatis ₱30/kilo sa puesto namin sa palengke. Mas mura kaysa supermarket.',
            'Ampalaya ₱40/kilo sa merkado namin. Bumaba na ang presyo!',
            'Nakakita ako ng sale sa SM — corned beef ₱28 lang! Pang-agahan pang-tanghalian pa!',
            'Bigas ang presyo bumaba na sa ₱48/kilo para sa sinandomeng. Magstok na tayo!',
            'Manok ₱160/kilo sa Monterey ngayon, mura na ito kesa dati na ₱185!',
            'Baboy kasim ₱230/kilo sa palengke — mas mura kesa sa pinakamalapit na grocery.',
            'Gulay sale ngayon sa Shopwise — sitaw ₱15/kilo at kangkong ₱20 isang bigkis.',
        ],
        'budget_win' => [
            'Nakakita rin ako ng tipid ngayong linggo — ₱800 lang ang groceries namin for 5 pax!',
            'Naging makatipid kami dahil sa meal planning. ₱2,500 lang ang budget namin this week.',
            'Binili ko na ang paninda ko sa palengke nang maaga — nakatipid ng ₱150 sa presyo!',
            'Sinubukan namin ang recipe dito — ₱120 lang para sa 4 na tao! Sulit na sulit!',
            'Isang linggo na kaming hindi nag-order ng takeout. Nakatipid kami ng ₱1,500!',
            'Gumamit ako ng ulong baboy para sa sinigang — ₱80 lang pero masustansya pa rin!',
            'Natutunan ko na gamitin ang mga natira sa ref para sa bagong putahe. Zero waste at tipid!',
            'Adobo sa ₱180 para sa buong pamilya. Ang sarap pa at marami pang matira para bukas!',
        ],
        'general' => [
            'May nakakaalam ba ng magandang recipe para sa pakbet? Gustong-gusto ng anak ko pero hindi ako marunong magluto.',
            'Kumusta ang mga grocery prices sa inyo ngayon? Sa amin medyo mahal pa rin ang isda.',
            'Paano kaya mababago ang panlasa ng adobo para hindi masyadong maalat? May tip ba kayo?',
            'Tanong lang: mas ok ba ang cooking sa kawali o sa wok para sa ginisang gulay?',
            'Nasubukan na ba ninyo ang monggo na may hipon? Bagong twist sa klasikong ulam!',
            'Pano kayo nag-iimpok sa grocery budget? Share naman ng inyong mga tips!',
            'Anu-anong gulay ang pinaka-sulit na bilhin ngayon para sa nutrition at presyo?',
            'May alam ba kayo ng magandang lutong-bahay na swak sa budget ng ₱500 para sa 4 katao?',
            'Nasubukan ko ang kare-kare gamit ang bagoong na homemade — mas masarap pa kaysa sa labas!',
            'Tip: ang pinakamatipid na paraan ng pagluluto ay ang paggamit ng slow cooker. Nakatipid ng kuryente at masarap pa!',
            'Anong ulam ang pinaka-favorite ng inyong pamilya na mura pero masustansya?',
            'Hindi ko pa nasubukan ang bistek — ano ba ang tamang marinating time para masarap?',
        ],
    ];

    private const COMMENT_BODIES = [
        'Salamat sa tip! Pupuntahan ko mamaya.',
        'Totoo ba ito? Malayo kaso worth it kung ganon presyo!',
        'Grabe ang tipid! Gagawin ko rin ito sa amin.',
        'Sharing ko ito sa grupo namin!',
        'Subukan ko rin ang recipe na ito. Mukhang masarap!',
        'Wow, hindi ko alam na ganyan ka-mura! Magpupunta na rin ako.',
        'Galing! Paano mo nagagawa na masustansya pa rin kahit mura?',
        'Ang tips mo ay talagang nakatulong sa aming pamilya!',
        'Dapat pala mas maaga akong naghanap ng ganyang deals.',
        'Nasubukan ko na rin ito at agree ako — sulit talaga!',
        'Maraming salamat! Ipo-post ko rin kapag may nakita akong mura.',
        'Uy, magkalapit tayo! Palagi kong pinupuntahan iyon.',
        'Pwede ba itong gawin kahit wala pang experience sa pagluluto?',
        'Napakaganda nito! Gagamitin ko ang tip na ito bukas.',
        'Laking tulong nito sa aming pamilya. Salamat talaga!',
        'Oo, nasubukan ko na rin — talagang masarap at mura!',
        'Paano mo nalaman ang presyo? May app ka bang ginagamit?',
        'Anong brand ang ginagamit mo? Gusto ko ring subukan.',
        'Hindi ako makapaniwala na ganyan ka-mura! Pupuntahan ko rin.',
        'Laking tipid! Gagawin ko rin ito bukas.',
        'Great idea! Hindi ko pa naisip ang ganyan.',
        'Mag-iingat ka lang sa kalidad kahit mura — pero kung okay naman, sulit!',
        'Ito ang kailangan ng mga families namin. Ibabahagi ko ito.',
        'Baka me sale pa bang iba? Interested ako sa mga grocery deals.',
        'Para sa amin, yung budget-friendly recipes ay nakakatulong talaga.',
    ];

    public function run(): void
    {
        // ── Wipe existing community content ──────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('post_comments')->truncate();
        DB::table('post_saves')->truncate();
        DB::table('post_reactions')->truncate();
        DB::table('post_dislikes')->truncate();
        DB::table('posts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Reset share_count on all recipes
        Recipe::query()->update(['share_count' => 0]);

        // ── All users / non-me users ──────────────────────────────────────────
        $allUsers   = User::all();
        $otherUsers = $allUsers->where('id', '!=', self::MY_USER_ID)->values();

        if ($otherUsers->isEmpty()) {
            $this->command->warn('No other users found — skipping seeder.');
            return;
        }

        // ── 20 random community posts from ALL users ──────────────────────────
        $types = ['price_tip', 'budget_win', 'general'];

        for ($i = 0; $i < 20; $i++) {
            $user   = $allUsers->random();
            $type   = $types[array_rand($types)];
            $bodies = self::POST_BODIES[$type];

            Post::create([
                'user_id'      => $user->id,
                'post_type'    => $type,
                'body'         => $bodies[array_rand($bodies)],
                'barangay'     => $user->barangay,
                'municipality' => $user->municipality,
                'puso_count'   => rand(0, 25),
                'dislike_count'=> rand(0, 5),
                'created_at'   => now()->subMinutes(rand(30, 60 * 72)),
                'updated_at'   => now()->subMinutes(rand(0, 30)),
            ]);
        }

        // ── 10 recipe_share posts ─────────────────────────────────────────────
        $publishedRecipes = Recipe::where('is_published', true)->take(15)->get();

        if ($publishedRecipes->isEmpty()) {
            $this->command->warn('No published recipes found — skipping recipe shares.');
            return;
        }

        $recipesToShare = $publishedRecipes->shuffle()->take(10);
        $sharePosts     = collect();

        foreach ($recipesToShare as $recipe) {
            $user = $otherUsers->random();

            $post = Post::create([
                'user_id'      => $user->id,
                'post_type'    => 'recipe_share',
                'recipe_id'    => $recipe->id,
                'body'         => $this->sampleShareCaption($recipe->title),
                'barangay'     => $user->barangay,
                'municipality' => $user->municipality,
                'puso_count'   => rand(5, 40),
                'dislike_count'=> rand(0, 3),
                'created_at'   => now()->subMinutes(rand(10, 60 * 48)),
                'updated_at'   => now()->subMinutes(rand(0, 20)),
            ]);

            Recipe::where('id', $recipe->id)->increment('share_count');
            $sharePosts->push($post);
        }

        // ── 7 of those recipe share posts get 10+ comments each ──────────────
        $postsWithComments = $sharePosts->shuffle()->take(7);

        foreach ($postsWithComments as $post) {
            $commentCount = rand(10, 18);
            for ($c = 0; $c < $commentCount; $c++) {
                $commenter = $otherUsers->random();
                $body      = self::COMMENT_BODIES[array_rand(self::COMMENT_BODIES)];

                PostComment::create([
                    'user_id'    => $commenter->id,
                    'post_id'    => $post->id,
                    'body'       => $body,
                    'created_at' => now()->subMinutes(rand(1, 60 * 24)),
                    'updated_at' => now()->subMinutes(rand(0, 10)),
                ]);
            }

            Post::where('id', $post->id)->update(['comments_count' => $commentCount]);
        }

        // ── Share one of MY recipes 10 times by different users ───────────────
        $myRecipe = Recipe::where('user_id', self::MY_USER_ID)
            ->where('is_published', true)
            ->first();

        if (!$myRecipe) {
            $this->command->warn('No published recipe found for user_id=14 — skipping 10× share.');
            return;
        }

        $sharers = $otherUsers->shuffle()->take(15);

        foreach ($sharers as $sharer) {
            Post::create([
                'user_id'      => $sharer->id,
                'post_type'    => 'recipe_share',
                'recipe_id'    => $myRecipe->id,
                'body'         => $this->sampleShareCaption($myRecipe->title),
                'barangay'     => $sharer->barangay,
                'municipality' => $sharer->municipality,
                'puso_count'   => rand(2, 30),
                'dislike_count'=> 0,
                'created_at'   => now()->subMinutes(rand(5, 60 * 36)),
                'updated_at'   => now()->subMinutes(rand(0, 15)),
            ]);

            Recipe::where('id', $myRecipe->id)->increment('share_count');
        }

        $this->command->info("Seeded: 20 posts, 10 recipe shares, 7 with 10+ comments, 1 recipe shared 15×.");
        $this->command->info("Recipe shared 15×: [{$myRecipe->id}] {$myRecipe->title}");
    }

    private function sampleShareCaption(string $title): string
    {
        $captions = [
            "Nasubukan ko na ang {$title} — ang sarap at hindi mahal!",
            "Sharing ko ito sa inyo — {$title} na akma sa budget!",
            "Subok na recipe na ito! Inirerekomenda ko ang {$title}.",
            "Para sa mga gustong magtipid — subukan ang {$title}!",
            "Favorite na ng pamilya ko ang {$title}. Murang-mura pa!",
            "Ito ang lutuin namin ngayon — {$title}. Masarap at sulit!",
            "Ito ang sagot sa ating budget woes — {$title}!",
            " ",
        ];

        return $captions[array_rand($captions)];
    }
}
