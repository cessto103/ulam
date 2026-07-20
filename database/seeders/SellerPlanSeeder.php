<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\BoostOption;
use App\Models\Faq;
use App\Models\SellerPlan;
use Illuminate\Database\Seeder;

// Idempotent — safe to re-run; admin edits to prices/limits survive because
// only the catalog *rows* are ensured, values are not overwritten once present.
class SellerPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'free', 'name' => 'Free', 'tagline' => 'Simulan ang iyong tindahan', 'max_stores' => 1, 'max_items_per_store' => 10, 'sort' => 0, 'prices' => []],
            ['slug' => 'basic', 'name' => 'Basic Seller', 'tagline' => 'Para sa lumalagong tindahan', 'max_stores' => 1, 'max_items_per_store' => 30, 'sort' => 1, 'prices' => ['7d' => 49, '15d' => 89, '1m' => 149, '1y' => 1499]],
            ['slug' => 'suki', 'name' => 'Suki Seller', 'tagline' => 'Para sa sikat na tindahan', 'max_stores' => 2, 'max_items_per_store' => 30, 'sort' => 2, 'prices' => ['7d' => 99, '15d' => 179, '1m' => 299, '1y' => 2999]],
            ['slug' => 'negosyante', 'name' => 'Negosyante', 'tagline' => 'Para sa seryosong negosyo', 'max_stores' => 5, 'max_items_per_store' => 50, 'sort' => 3, 'prices' => ['7d' => 179, '15d' => 329, '1m' => 549, '1y' => 5499]],
        ];

        foreach ($plans as $data) {
            $prices = $data['prices'];
            unset($data['prices']);

            $plan = SellerPlan::firstOrCreate(['slug' => $data['slug']], $data);

            foreach ($prices as $duration => $price) {
                $plan->prices()->firstOrCreate(
                    ['duration' => $duration],
                    ['price' => $price, 'is_active' => true],
                );
            }
        }

        $boosts = [
            ['target' => 'tindahan', 'duration_days' => 3, 'price' => 39, 'sort' => 0],
            ['target' => 'tindahan', 'duration_days' => 7, 'price' => 79, 'sort' => 1],
            ['target' => 'recipe', 'duration_days' => 3, 'price' => 29, 'sort' => 2],
            ['target' => 'recipe', 'duration_days' => 7, 'price' => 59, 'sort' => 3],
        ];

        foreach ($boosts as $data) {
            BoostOption::firstOrCreate(
                ['target' => $data['target'], 'duration_days' => $data['duration_days']],
                $data,
            );
        }

        $settings = [
            'payments_enabled' => '1',
            'gcash_number' => '09XX XXX XXXX',       // TODO: owner fills in from admin Settings
            'gcash_account_name' => 'uLam',          // TODO: owner fills in from admin Settings
            'payment_instructions' => "1. Open your GCash app and tap Send Money.\n2. Send the exact amount to the number above.\n3. Copy the 13-digit Reference No. from your receipt.\n4. Paste it below and submit — we verify within 24 hours.",
            'payment_support_note' => 'Problema sa bayad? Message us through Help & Support.',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $faqs = [
            [
                'question' => 'How do I pay for a seller subscription?',
                'question_tl' => 'Paano magbayad para sa seller subscription?',
                'answer' => "Choose a plan, send the exact amount to our GCash number, then submit the reference number from your GCash receipt. We verify payments within 24 hours.",
                'answer_tl' => "Pumili ng plan, ipadala ang eksaktong halaga sa aming GCash number, at i-submit ang reference number mula sa iyong GCash receipt. Vine-verify namin ang bayad sa loob ng 24 oras.",
                'category' => 'payment',
                'sort' => 0,
            ],
            [
                'question' => 'Where do I find the GCash reference number?',
                'question_tl' => 'Saan makikita ang GCash reference number?',
                'answer' => 'After sending money, GCash shows a receipt with a 13-digit Ref No. You can also find it in your GCash transaction history.',
                'answer_tl' => 'Pagkatapos mag-send ng pera, may makikitang resibo sa GCash na may 13-digit Ref No. Makikita rin ito sa iyong GCash transaction history.',
                'category' => 'payment',
                'sort' => 1,
            ],
            [
                'question' => 'What happens when my subscription expires?',
                'question_tl' => 'Ano ang mangyayari kapag nag-expire ang subscription ko?',
                'answer' => 'Your account goes back to the Free plan. Extra stores become private and hidden from buyers, but nothing is deleted, and everything comes back when you subscribe again.',
                'answer_tl' => 'Babalik sa Free plan ang iyong account. Magiging pribado ang mga sobrang tindahan, pero walang mabubura, at babalik ang lahat kapag nag-subscribe ka ulit.',
                'category' => 'subscription',
                'sort' => 2,
            ],
            [
                'question' => 'Can I get a refund?',
                'question_tl' => 'Pwede ba akong mag-refund?',
                'answer' => 'Yes, within 48 hours of activation, no questions asked. After that, 7-day, 15-day, and monthly plans are non-refundable; yearly plans are reviewed case by case.',
                'answer_tl' => 'Oo, sa loob ng 48 oras mula sa activation, walang tanong. Pagkatapos nito, hindi na refundable ang 7-day, 15-day, at monthly plans; ang yearly plans ay dinedesisyunan isa-isa.',
                'category' => 'payment',
                'sort' => 3,
            ],
            [
                'question' => 'Why is my payment still pending?',
                'question_tl' => 'Bakit pending pa rin ang bayad ko?',
                'answer' => 'Payments are verified by hand, usually within 24 hours. If it takes longer, open a ticket in Help & Support with your reference number.',
                'answer_tl' => 'Ang mga bayad ay chine-check nang mano-mano, kadalasan sa loob ng 24 oras. Kung matagal pa, mag-open ng ticket sa Help & Support kasama ang iyong reference number.',
                'category' => 'payment',
                'sort' => 4,
            ],
        ];

        foreach ($faqs as $data) {
            Faq::firstOrCreate(['question' => $data['question']], $data);
        }
    }
}
