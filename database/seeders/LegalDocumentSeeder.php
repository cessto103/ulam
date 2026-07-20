<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->seedDocument('terms', 'Terms & Conditions', $this->termsContent(), $admin?->id);
        $this->seedDocument('privacy', 'Privacy Policy', $this->privacyContent(), $admin?->id);

        $this->command->info('LegalDocumentSeeder done.');
    }

    private function seedDocument(string $slug, string $title, string $content, ?int $adminId): void
    {
        $doc = LegalDocument::updateOrCreate(['slug' => $slug], ['title' => $title]);

        // Only seed v1.0.0 once, never clobber admin-managed versions.
        if ($doc->versions()->exists()) {
            return;
        }

        $doc->versions()->create([
            'version' => '1.0.0',
            'changelog' => 'Initial release.',
            'content_md' => $content,
            'status' => 'published',
            'author_id' => $adminId,
            'published_by' => $adminId,
            'published_at' => now(),
        ]);
    }

    private function termsContent(): string
    {
        return <<<'MD'
# Terms & Conditions

**Effective date:** July 14, 2026

Welcome to **uLam** ("uLam", "we", "us", or "our"). uLam is a mobile application that helps Filipino households plan meals within a budget, discover recipes, view community-reported market prices, and connect with local food sellers. By creating an account or using the app, you agree to these Terms & Conditions ("Terms"). Please read them carefully.

## 1. Acceptance of Terms

By registering for, accessing, or using uLam, you confirm that you have read, understood, and agree to be bound by these Terms and our Privacy Policy. If you do not agree, please do not use the app.

## 2. Eligibility

You must be at least 13 years old to use uLam. If you are under 18, you may use the app only with the consent and supervision of a parent or legal guardian. By using seller features (store listings, subscriptions, boosts), you confirm that you are at least 18 years old and legally capable of entering into contracts in the Philippines.

## 3. User Accounts

- You must provide accurate and complete information when creating an account.
- You may maintain only one personal account. Accounts are personal and may not be sold or transferred.
- You are responsible for all activity that happens under your account.

## 4. Account Security

- Keep your password confidential. We will never ask for your password outside the app.
- Notify us immediately through Help & Support if you suspect unauthorized use of your account.
- We are not liable for losses caused by your failure to protect your account credentials.

## 5. The uLam Service: Information, Not a Store

uLam is an **information and community platform**. We do not sell food, process food orders, or deliver goods.

- **Prices shown in the app are community-reported or estimated.** Actual prices at any market, stall, or store may differ. Always confirm the price with the seller before buying.
- **Transactions happen outside the app.** When you buy from a seller you found on uLam, that purchase is strictly between you and the seller. uLam is not a party to the sale, does not guarantee product quality, availability, safety, or price, and does not handle disputes between buyers and sellers.
- AI-generated meal plans and cost estimates are suggestions only. They are not medical, nutritional, or financial advice.

## 6. Payments to uLam

Certain seller features (subscription plans and listing boosts) are paid services.

- Payments are made through the channels shown in the app (e.g., GCash, or our payment partners). When manual payment is used, activation happens only after our team verifies your payment reference, normally within 24 hours.
- Prices for subscriptions and boosts are shown in the app and may change; changes never affect a period you have already paid for.
- Each payment reference number may be used only once. Submitting a false or reused reference is grounds for account suspension.

## 7. Refunds & Cancellations

- Seller subscription payments may be refunded upon request **within 48 hours** of activation, no questions asked. A refund ends the related access immediately.
- After 48 hours, fees for the current period are non-refundable, but you may cancel renewal at any time; access continues until the end of the paid period.
- Boost payments are non-refundable once the boost has started, except where we fail to deliver the boost.
- Refunds are sent back through the same channel used to pay, and may take several business days to arrive.

## 8. Seller Responsibilities

If you list a store or stall on uLam, you agree to:

- Provide truthful information about your store, its location, hours, and prices;
- Keep your listings reasonably up to date;
- Honor the prices you publish, or clearly inform buyers when a price has changed;
- Comply with all applicable Philippine laws, including business permit, food safety, and consumer protection requirements;
- Respond to community price reports honestly.

We may remove listings, hide stores, or suspend seller privileges for inaccurate, misleading, or unlawful listings.

## 9. Community Content and Conduct

uLam includes community features: posts, recipes, comments, ratings, price reports, and photos ("User Content").

- You keep ownership of the User Content you create, but you grant uLam a non-exclusive, royalty-free, worldwide license to host, display, and distribute it within the service (for example, showing your recipe to other users).
- You are responsible for the content you post. Post only what you have the right to share.
- Ratings and reviews must reflect your genuine experience. Fake reviews, rating manipulation, and review extortion are prohibited.

## 10. Prohibited Activities

You agree not to:

- Post false prices, fake stores, or misleading listings;
- Harass, threaten, or defame other users or sellers;
- Post unlawful, obscene, or hateful content;
- Impersonate any person or misrepresent your affiliation;
- Attempt to access other users' accounts or our systems without authorization;
- Scrape, copy, or resell uLam data without written permission;
- Use the app for spam, fraud, money laundering, or any illegal purpose;
- Interfere with the operation of the app (e.g., overloading, reverse engineering, injecting malicious code).

## 11. Intellectual Property

The uLam name, logo, app design, and all content we create are owned by uLam and protected by law. You may not use our branding without written permission. Official recipes and app content are provided for your personal, non-commercial use.

## 12. Moderation, Suspension & Termination

- We may review, moderate, or remove any content that violates these Terms.
- We may suspend or permanently ban accounts that violate these Terms, abuse other users, or attempt to defraud the platform, with or without prior notice, depending on severity.
- You may delete your account at any time in **Settings → Danger Zone**. Deletion is permanent.
- Sections that by their nature should survive termination (e.g., limitation of liability, indemnification) survive it.

## 13. Disclaimers

THE APP IS PROVIDED "AS IS" AND "AS AVAILABLE." TO THE MAXIMUM EXTENT PERMITTED BY LAW, WE DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. WE DO NOT WARRANT THAT PRICES ARE ACCURATE, THAT SELLERS WILL HONOR LISTED PRICES, OR THAT THE APP WILL BE UNINTERRUPTED OR ERROR-FREE.

## 14. Limitation of Liability

To the maximum extent permitted by Philippine law, uLam and its operator shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or for lost profits, arising from your use of the app, including disputes with sellers, reliance on price information, or food-related issues from purchases made with third parties. Where liability cannot be excluded, our total liability is limited to the amount you paid to uLam in the three (3) months before the claim.

## 15. Indemnification

You agree to indemnify and hold harmless uLam and its operator from claims, damages, and expenses (including reasonable attorney's fees) arising from your User Content, your use of the app, your transactions with other users, or your violation of these Terms.

## 16. Changes to These Terms

We may update these Terms from time to time. When we publish a new version, the app will show you the updated Terms and ask you to accept them before continuing. The version number and effective date appear at the top of this document.

## 17. Governing Law

These Terms are governed by the laws of the Republic of the Philippines. Any disputes shall be brought before the proper courts of the Philippines.

## 18. Contact

Questions about these Terms? Reach us through **Help & Support** inside the app, or by email at **cessto103@gmail.com**.
MD;
    }

    private function privacyContent(): string
    {
        return <<<'MD'
# Privacy Policy

**Effective date:** July 14, 2026

This Privacy Policy explains how **uLam** ("uLam", "we", "us") collects, uses, shares, and protects your personal information, in accordance with the Philippine **Data Privacy Act of 2012 (Republic Act No. 10173)** and its implementing rules.

## 1. Information We Collect

**Information you give us:**

- **Account details**: name, username, email address, password (stored encrypted/hashed, never in plain text), and an optional secondary email;
- **Profile details**: photo, bio, household size, dietary preferences;
- **Location details**: barangay, city/municipality, province, region, and (only if you use location features) your GPS coordinates;
- **Content you create**: recipes, posts, comments, ratings, price reports, store listings, and photos;
- **Payment references**: when you pay for seller features, we record the payment reference number and amount. **We never see or store your GCash PIN, bank credentials, or full wallet details.**

**Information collected automatically:**

- **Usage data**: features you use, content you view (e.g., which recipes you open), XP and activity streaks;
- **Device data**: device model and operating system (used for support and push notifications);
- **Approximate network information**: such as IP address, in server logs kept for security.

We do **not** use third-party advertising trackers.

## 2. How We Use Your Information

- To operate the app: accounts, meal plans, budgets, community feeds, price information, and seller listings;
- To generate AI meal plans: your budget, household size, and dietary preferences are sent to our AI provider to produce your plan;
- To show nearby markets and stores: your coordinates are used to search locations near you;
- To process and verify payments for seller features;
- To send service emails (e.g., verification codes, password reset codes) and push notifications you can control in your device settings;
- To keep the community safe: moderation, fraud prevention, and enforcing our Terms;
- To improve the app using aggregated, non-identifying statistics.

We do not sell your personal information. Ever.

## 3. Sharing of Information

We share data only with the service providers needed to run uLam:

- **Anthropic (Claude AI)**: receives your budget, household size, and dietary preferences (not your name or contact details) to generate meal plans;
- **Resend**: delivers our emails (verification and password-reset codes) to your email address;
- **Expo**: delivers push notifications to your device token;
- **OpenStreetMap / Nominatim**: receives coordinates (never your identity) for location lookups;
- **Payment providers** (e.g., PayMongo, when active): process payments for seller features;
- **Authorities**: only when required by law, court order, or to protect users from harm.

Other users can see what you choose to publish: your profile name/username/photo, your posts, recipes, comments, ratings, and store listings. Your email, exact location coordinates, and budget details are never shown to other users.

## 4. Data Retention

- Account data is kept while your account is active.
- If you delete your account, your profile, content, stores, budgets, and activity data are **permanently deleted**. Payment records are retained as required for financial and legal record-keeping.
- Security logs are kept for a limited period and then deleted.

## 5. Data Security

- Passwords are hashed; sensitive secrets (such as two-factor keys) are stored encrypted;
- Access to production data is restricted to the operator;
- Verification codes expire within minutes and are single-use.

No system is 100% secure, but we work to protect your data with industry-standard measures appropriate to our size.

## 6. Your Rights

Under the Data Privacy Act, you have the right to:

- **Access** the personal data we hold about you;
- **Correct** inaccurate data (most details can be edited directly in Settings and Edit Profile);
- **Delete** your data: available anytime in **Settings → Danger Zone → Delete Account**;
- **Object** to certain processing, and **withdraw consent** where processing is based on consent;
- **Data portability**: request a copy of your data in a usable format;
- **Lodge a complaint** with the National Privacy Commission (privacy.gov.ph).

To exercise any of these rights, contact us through Help & Support or the email below.

## 7. Children's Privacy

uLam is not directed at children under 13, and we do not knowingly collect personal information from them. If you believe a child under 13 has created an account, contact us and we will delete it.

## 8. Marketing Communications

We currently send only **service communications** (verification codes, payment confirmations, important notices). If we ever introduce marketing messages, they will be opt-in and easy to unsubscribe from.

## 9. Changes to This Policy

When we publish an updated Privacy Policy, the app will show it to you and ask for your acceptance before you continue. The version number and effective date appear at the top of this document.

## 10. Contact

For privacy questions or requests, reach us through **Help & Support** inside the app, or by email at **cessto103@gmail.com**.

*uLam: Para sa bawat Pilipinong pamilya.*
MD;
    }
}
