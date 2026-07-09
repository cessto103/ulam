<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\GovernmentPriceReference;
use Illuminate\Support\Facades\Log;

class PriceIntelligenceService
{
    private const MODEL       = 'claude-haiku-4-5-20251001';
    private const MAX_TOKENS  = 1500;
    private const CATEGORIES  = ['isda', 'karne', 'gulay', 'bigas', 'prutas', 'sangkap', 'itlog', 'manok', 'baboy', 'baka', 'iba pa'];
    private const GOV_SOURCES = ['da_bantay_presyo', 'dti_srp'];

    // PSA region codes → human-readable names, so web search gets a usable query term
    private const REGION_NAMES = [
        'NCR'   => 'National Capital Region (NCR)',
        'CAR'   => 'Cordillera Administrative Region (CAR)',
        'I'     => 'Ilocos Region (Region I)',
        'II'    => 'Cagayan Valley (Region II)',
        'III'   => 'Central Luzon (Region III)',
        'IV-A'  => 'CALABARZON (Region IV-A)',
        'IV-B'  => 'MIMAROPA (Region IV-B)',
        'V'     => 'Bicol Region (Region V)',
        'VI'    => 'Western Visayas (Region VI)',
        'VII'   => 'Central Visayas (Region VII)',
        'VIII'  => 'Eastern Visayas (Region VIII)',
        'IX'    => 'Zamboanga Peninsula (Region IX)',
        'X'     => 'Northern Mindanao (Region X)',
        'XI'    => 'Davao Region (Region XI)',
        'XII'   => 'SOCCSKSARGEN (Region XII)',
        'XIII'  => 'Caraga (Region XIII)',
        'BARMM' => 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)',
    ];

    private Client $ai;

    public function __construct()
    {
        $this->ai = new Client(apiKey: config('services.anthropic.key'));
    }

    /**
     * Search the web for current prices at the given market and persist them.
     * Returns the number of price rows saved.
     */
    public function refreshMarket(Market $market): int
    {
        $prompt = $this->buildPrompt($market);

        try {
            $message = $this->ai->messages->create(
                maxTokens: self::MAX_TOKENS,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: self::MODEL,
                tools: [[
                    'type' => 'web_search_20260209',
                    'name' => 'web_search',
                    'allowed_callers' => ['direct'],
                ]],
                system: 'You are a price monitoring assistant for the Philippines. '
                    . 'Use web search to find current retail prices of basic commodities. '
                    . 'Always respond with a valid JSON array and nothing else outside the JSON block.',
            );
        } catch (\Throwable $e) {
            Log::error("PriceIntelligenceService: API error for market {$market->id}", ['error' => $e->getMessage()]);
            throw $e;
        }

        $items = $this->extractItems($message);
        if (empty($items)) {
            Log::info("PriceIntelligenceService: no items found for {$market->name}");
            return 0;
        }

        return $this->persistItems($market, $items);
    }

    /**
     * Search the web for the latest DA "Bantay Presyo" price monitoring report and
     * DTI Suggested Retail Price (SRP) bulletin covering the given region, and persist
     * them as official reference prices (distinct from market/community prices).
     * Returns the number of reference rows saved.
     */
    public function refreshGovernmentPrices(string $region): int
    {
        $prompt = $this->buildGovPrompt($region);

        try {
            $message = $this->ai->messages->create(
                maxTokens: self::MAX_TOKENS,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: self::MODEL,
                tools: [[
                    'type' => 'web_search_20260209',
                    'name' => 'web_search',
                    'allowed_callers' => ['direct'],
                ]],
                system: 'You are a government price-bulletin research assistant for the Philippines. '
                    . 'Use web search to find the latest official DA Bantay Presyo price monitoring report '
                    . 'and DTI Suggested Retail Price (SRP) bulletin. '
                    . 'Always respond with a valid JSON array and nothing else outside the JSON block.',
            );
        } catch (\Throwable $e) {
            Log::error("PriceIntelligenceService: government price API error for region {$region}", ['error' => $e->getMessage()]);
            throw $e;
        }

        $items = $this->extractGovItems($message);
        if (empty($items)) {
            Log::info("PriceIntelligenceService: no government reference prices found for {$region}");
            return 0;
        }

        return $this->persistGovItems($region, $items);
    }

    // ── Private helpers ──────────────────────────────────────────────────────────

    private function buildGovPrompt(string $region): string
    {
        $cats = implode(', ', self::CATEGORIES);
        $regionName = self::REGION_NAMES[$region] ?? $region;
        return <<<PROMPT
Search the web for the latest official Philippine government price references for
{$regionName} (region code: {$region}):

1. DA (Department of Agriculture) "Bantay Presyo" daily price monitoring report — covers
   wet-market goods such as rice (bigas), fish (isda), vegetables (gulay), chicken (manok),
   pork (baboy), beef (baka), and eggs (itlog).
2. DTI (Department of Trade and Industry) Suggested Retail Price (SRP) bulletin for basic
   necessities and prime commodities — covers packaged goods such as rice, sugar, cooking
   oil, canned goods, instant noodles, and LPG.

Return ONLY a JSON array with this exact structure — no markdown, no explanation:
[
  {"source": "da_bantay_presyo", "item_name": "Galunggong", "price_min": 160.00, "price_max": 180.00, "unit": "kg", "category": "isda", "bulletin_date": "2026-07-01", "source_note": "DA Price Watch NCR"},
  {"source": "dti_srp", "item_name": "Sardines 155g", "price_min": 18.00, "price_max": 18.00, "unit": "can", "category": "sangkap", "bulletin_date": "2026-06-15", "source_note": "DTI SRP Bulletin"}
]

Rules:
- source must be one of: da_bantay_presyo, dti_srp
- category must be one of: {$cats}
- unit is typically "kg", "pc", "can", "bundle", "liter", "g", "tali", or "tank" (for LPG)
- If the bulletin gives a single price rather than a range, set price_min and price_max to the same value
- bulletin_date is the date the bulletin covers, in YYYY-MM-DD format, or null if unknown
- source_note is a short human-readable citation (e.g. "DA Price Watch, July 2026"), or null
- Only include items where you found an actual reported price — never guess
- If no prices found for this region, return exactly: []
PROMPT;
    }

    /**
     * @return array<int, array{source: string, item_name: string, price_min: float, price_max: float, unit: string, category: ?string, bulletin_date: ?string, source_note: ?string}>
     */
    private function extractGovItems(\Anthropic\Messages\Message $message): array
    {
        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        if (!preg_match('/\[[\s\S]*\]/m', $text, $match)) {
            return [];
        }

        $decoded = json_decode($match[0], true);
        if (!is_array($decoded)) {
            return [];
        }

        $valid = [];
        foreach ($decoded as $row) {
            if (!isset($row['source'], $row['item_name'], $row['price_min'], $row['price_max'], $row['unit'])) continue;
            if (!in_array($row['source'], self::GOV_SOURCES, true)) continue;

            $category = $row['category'] ?? null;
            if ($category !== null && !in_array($category, self::CATEGORIES, true)) {
                $category = null;
            }

            $min = (float) $row['price_min'];
            $max = (float) $row['price_max'];
            if ($min <= 0 || $max <= 0 || $min > 10_000 || $max > 10_000) continue;
            if ($max < $min) [$min, $max] = [$max, $min];

            $bulletinDate = null;
            if (!empty($row['bulletin_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['bulletin_date'])) {
                $bulletinDate = $row['bulletin_date'];
            }

            $valid[] = [
                'source'        => $row['source'],
                'item_name'     => trim((string) $row['item_name']),
                'price_min'     => $min,
                'price_max'     => $max,
                'unit'          => trim((string) $row['unit']),
                'category'      => $category,
                'bulletin_date' => $bulletinDate,
                'source_note'   => isset($row['source_note']) ? trim((string) $row['source_note']) : null,
            ];
        }

        return $valid;
    }

    private function persistGovItems(string $region, array $items): int
    {
        $saved = 0;
        foreach ($items as $item) {
            GovernmentPriceReference::updateOrCreate(
                [
                    'source'    => $item['source'],
                    'item_name' => $item['item_name'],
                    'region'    => $region,
                ],
                [
                    'category'      => $item['category'],
                    'price_min'     => $item['price_min'],
                    'price_max'     => $item['price_max'],
                    'unit'          => $item['unit'],
                    'bulletin_date' => $item['bulletin_date'],
                    'source_note'   => $item['source_note'],
                    'updated_at'    => now(),
                ]
            );
            $saved++;
        }
        Log::info("PriceIntelligenceService: saved {$saved} government reference prices for {$region}");
        return $saved;
    }

    private function buildPrompt(Market $market): string
    {
        $cats = implode(', ', self::CATEGORIES);
        return <<<PROMPT
Search the web for current retail prices of basic commodities at "{$market->name}"
in {$market->barangay}, {$market->municipality}, Philippines.

Look for items such as rice (bigas), fish (isda, galunggong, bangus, tilapia),
vegetables (gulay, kamatis, sibuyas, bawang, kangkong), chicken (manok), pork (baboy),
beef (baka), eggs (itlog), fruits (prutas), and cooking ingredients (sangkap).

Return ONLY a JSON array with this exact structure — no markdown, no explanation:
[
  {"item_name": "Galunggong", "price_per_unit": 180.00, "unit": "kg", "category": "isda"},
  {"item_name": "Bigas (NFA)", "price_per_unit": 45.00, "unit": "kg", "category": "bigas"}
]

Rules:
- category must be one of: {$cats}
- unit is typically "kg", "pc", "bundle", "liter", "g", or "tali"
- Only include items where you found an actual reported price — never guess
- If no prices found for this market, return exactly: []
PROMPT;
    }

    /**
     * Extract price items from the Claude response, handling tool_use turns.
     *
     * @return array<int, array{item_name: string, price_per_unit: float, unit: string, category: string}>
     */
    private function extractItems(\Anthropic\Messages\Message $message): array
    {
        // Claude may return text directly or after web_search tool turns
        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        // Extract JSON array from anywhere in the text
        if (!preg_match('/\[[\s\S]*\]/m', $text, $match)) {
            return [];
        }

        $decoded = json_decode($match[0], true);
        if (!is_array($decoded)) {
            return [];
        }

        $valid = [];
        foreach ($decoded as $row) {
            if (!isset($row['item_name'], $row['price_per_unit'], $row['unit'], $row['category'])) continue;
            if (!in_array($row['category'], self::CATEGORIES, true)) continue;
            $price = (float) $row['price_per_unit'];
            if ($price <= 0 || $price > 10_000) continue;

            $valid[] = [
                'item_name'      => trim((string) $row['item_name']),
                'price_per_unit' => $price,
                'unit'           => trim((string) $row['unit']),
                'category'       => $row['category'],
            ];
        }

        return $valid;
    }

    private function persistItems(Market $market, array $items): int
    {
        $saved = 0;
        foreach ($items as $item) {
            MarketPrice::updateOrCreate(
                [
                    'market_id'   => $market->id,
                    'tindahan_id' => null,
                    'item_name'   => $item['item_name'],
                ],
                [
                    'category'         => $item['category'],
                    'price_per_unit'   => $item['price_per_unit'],
                    'unit'             => $item['unit'],
                    'is_available'     => true,
                    'last_updated_by'  => null,
                    'updated_at'       => now(),
                ]
            );
            $saved++;
        }
        Log::info("PriceIntelligenceService: saved {$saved} prices for {$market->name}");
        return $saved;
    }
}
