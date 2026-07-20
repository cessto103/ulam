<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Safe to re-run any time -- every row is upserted by its English question.
 * Standalone: php artisan db:seed --class=FaqSeeder
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'rewards',
                'sort' => 10,
                'question' => 'How do I earn XP?',
                'question_tl' => 'Paano ako makakakuha ng XP?',
                'answer' => "You earn XP by using the app in everyday ways: generating a meal plan, logging your spending, reporting a price, saving a recipe, helping with a shared shopping list, or posting to the community. Check the Awards screen for Today's Tasks, This Week, and This Month — each one shows exactly how much XP it's worth. Lifetime Achievements (like Recipe Collector or Presyo Patrol) unlock Bronze, Silver, Gold, and Diamond badges as you build up a habit, with bigger XP the higher the tier.",
                'answer_tl' => 'Kumikita ka ng XP sa pang-araw-araw na paggamit ng app: paggawa ng meal plan, pag-log ng gastos, pag-report ng presyo, pag-save ng recipe, pagtulong sa shared shopping list, o pag-post sa komunidad. Tingnan ang Awards screen para sa Mga Gawain Ngayon, Ngayong Linggo, at Ngayong Buwan — makikita doon kung magkano ang XP ng bawat isa. Ang mga Achievement (tulad ng Recipe Collector o Presyo Patrol) ay nagbibigay ng Bronze, Silver, Gold, at Diamond badge habang bumubuo ka ng ugali, mas malaki ang XP sa mas mataas na tier.',
            ],
            [
                'category' => 'rewards',
                'sort' => 11,
                'question' => 'What are Reward Tiers? How do I unlock rewards?',
                'question_tl' => 'Ano ang Reward Tiers? Paano ako makakakuha ng rewards?',
                'answer' => "Reward Tiers are special milestones set up by our team — each one requires completing a specific set of Tasks or Achievements (and sometimes reaching a certain XP amount). Unlocking one grants a real reward: free days of uLam Premium, a free boost for one of your recipes or your store, or a cosmetic badge on your profile. Check the Rewards section on your Awards screen to see which tiers are still locked and exactly what's left to complete.",
                'answer_tl' => 'Ang Reward Tiers ay mga espesyal na milestone na itinakda ng aming team — kailangan mong kumpletuhin ang partikular na Tasks o Achievements (minsan may kasamang kinakailangang XP). Kapag na-unlock mo ito, may tunay na gantimpala: libreng araw ng uLam Premium, libreng boost para sa isa sa iyong recipe o tindahan, o cosmetic badge sa iyong profile. Tingnan ang Rewards section sa iyong Awards screen para makita kung anong tiers ang naka-lock pa at kung ano ang natitira mong gawin.',
            ],
            [
                'category' => 'prices',
                'sort' => 12,
                'question' => 'Are the prices shown in uLam accurate?',
                'question_tl' => 'Tama ba ang mga presyong nakikita sa uLam?',
                'answer' => 'Prices in uLam are mostly reported by our community — regular users like you who report what they actually paid at a market or store. Because prices genuinely change often (season, supply, specific stall, time of day) and older reports don\'t disappear automatically, a price you see might not exactly match what you\'ll pay today. Always treat prices here as a helpful estimate, not a guaranteed quote — use your judgment once you\'re at the market.',
                'answer_tl' => 'Karamihan sa mga presyo sa uLam ay galing sa community — mga regular na user tulad mo na nag-rereport ng kanilang aktwal na binayaran sa palengke o tindahan. Dahil talagang madalas magbago ang presyo (season, supply, partikular na puwesto, oras ng araw) at hindi awtomatikong nawawala ang mga lumang report, posibleng hindi eksaktong tugma ang presyong makikita mo sa babayaran mo ngayon. Ituring lagi ang mga presyo dito bilang gabay lamang, hindi garantisadong halaga — gamitin ang sariling pagpapasya pagdating mo sa palengke.',
            ],
            [
                'category' => 'prices',
                'sort' => 13,
                'question' => 'How can I help keep prices accurate?',
                'question_tl' => 'Paano ako makakatulong para maging tama ang mga presyo?',
                'answer' => "Report a price every time you shop! Reporting is quick, earns you XP, and pushes older reports down so the freshest price shows first. The more people who report regularly in your area, the more reliable prices become for everyone. If you see a price that looks clearly outdated or wrong, reporting the current one is the best way to fix it — there's no separate 'flag as wrong' feature, since a fresh report simply takes priority.",
                'answer_tl' => "Mag-report ng presyo tuwing namimili ka! Mabilis lang ang pag-report, kikita ka ng XP, at nasa unahan ang pinakabagong report kaysa sa mga luma. Habang mas maraming taong regular na nag-rereport sa inyong lugar, mas maasahan ang presyo para sa lahat. Kung may nakita kang presyo na mukhang luma na o mali, ang pag-report ng kasalukuyang presyo ang pinakamagandang paraan para maayos ito — walang hiwalay na 'i-flag bilang mali,' dahil ang bagong report na mismo ang nauuna.",
            ],
            [
                'category' => 'community',
                'sort' => 14,
                'question' => 'What are the community guidelines? What happens if I\'m reported?',
                'question_tl' => 'Ano ang mga alituntunin ng komunidad? Ano ang mangyayari kung ma-report ako?',
                'answer' => "uLam is a shared community of everyday Filipino households helping each other save money — please be respectful and kind in your posts, comments, and price reports. Harassment, hate speech, scams, spam, or posting fake/misleading prices are not allowed. If another user reports your content or behavior, our team reviews it — repeated or serious violations can lead to a warning, a temporary restriction, or a permanent ban depending on the severity. We look at the report itself, not who sent it, and we never share a reporter's identity with the person they reported.",
                'answer_tl' => 'Ang uLam ay isang shared na komunidad ng mga ordinaryong pamilyang Pilipino na nagtutulungan para makatipid — maging magalang at mabait sa iyong mga post, komento, at price report. Hindi pinapayagan ang panliligalig, hate speech, scam, spam, o pag-post ng peke o nakaliligaw na presyo. Kung na-report ng ibang user ang iyong content o pag-uugali, sinusuri ito ng aming team — ang paulit-ulit o seryosong paglabag ay maaaring humantong sa babala, pansamantalang paghihigpit, o permanenteng ban depende sa bigat. Sinusuri namin ang report mismo, hindi kung sino ang nag-report, at hindi namin ibinubunyag ang pagkakakilanlan ng nag-report sa taong kanilang ni-report.',
            ],
        ];

        foreach ($faqs as $f) {
            Faq::updateOrCreate(
                ['question' => $f['question']],
                $f + ['is_published' => true]
            );
        }
    }
}
