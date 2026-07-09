<?php

namespace App\Console\Commands;

use Anthropic\Client as AnthropicClient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedRecipes extends Command
{
    protected $signature = 'ulam:seed-recipes
                            {--count=100 : Total number of recipes to generate}
                            {--budget= : Seed only this budget tier (budget_100|budget_200|budget_400|budget_400plus)}
                            {--fresh : Delete all existing AI recipes before seeding}';

    protected $description = 'Generate Filipino recipes using Claude and seed them into the database';

    private AnthropicClient $claude;

    private array $budgetTiers = [
        'budget_100' => [
            'label'       => 'Under ₱100',
            'range'       => '₱50–₱100',
            'description' => 'Very budget-friendly dishes using the cheapest available ingredients',
        ],
        'budget_200' => [
            'label'       => '₱100–₱200',
            'range'       => '₱100–₱200',
            'description' => 'Affordable everyday Filipino family dishes',
        ],
        'budget_400' => [
            'label'       => '₱200–₱400',
            'range'       => '₱200–₱400',
            'description' => 'Mid-range Filipino dishes, good for special days',
        ],
        'budget_400plus' => [
            'label'       => '₱400+',
            'range'       => '₱400–₱800',
            'description' => 'Weekend or celebration dishes with premium ingredients',
        ],
    ];

    public function handle(): int
    {
        $this->claude = new AnthropicClient(
            apiKey: config('services.anthropic.key'),
        );

        if ($this->option('fresh')) {
            $deleted = Recipe::where('source', 'ai_generated')->count();
            Recipe::where('source', 'ai_generated')->delete();
            $this->line("  Deleted {$deleted} existing AI-generated recipes.");
        }

        $totalCount = (int) $this->option('count');
        $onlyBudget = $this->option('budget');

        $tiers = $onlyBudget
            ? array_filter($this->budgetTiers, fn($k) => $k === $onlyBudget, ARRAY_FILTER_USE_KEY)
            : $this->budgetTiers;

        if (empty($tiers)) {
            $this->error("Unknown budget tier: {$onlyBudget}");
            return self::FAILURE;
        }

        $perTier = (int) ceil($totalCount / count($tiers));
        $grandTotal = 0;

        foreach ($tiers as $tag => $meta) {
            $this->newLine();
            $this->info("── {$meta['label']} ({$tag}) ── generating {$perTier} recipes…");

            $generated = $this->generateTier($tag, $meta, $perTier);
            $grandTotal += $generated;

            $this->line("  ✓ {$generated} recipes saved");
        }

        $this->newLine();
        $this->info("Done! {$grandTotal} recipes seeded total.");

        return self::SUCCESS;
    }

    private function generateTier(string $tag, array $meta, int $count): int
    {
        // Batch into calls of 10 recipes each
        $batchSize  = 10;
        $batches    = (int) ceil($count / $batchSize);
        $saved      = 0;

        $existingTitles = Recipe::where('budget_tag', $tag)
            ->pluck('title')
            ->map(fn($t) => strtolower($t))
            ->toArray();

        for ($i = 0; $i < $batches; $i++) {
            $need = min($batchSize, $count - $saved);
            $this->line("  Batch " . ($i + 1) . "/{$batches} — requesting {$need} recipes…");

            try {
                $recipes = $this->callClaude($tag, $meta, $need, $existingTitles);
            } catch (\Throwable $e) {
                $this->error("  Batch failed: " . $e->getMessage());
                sleep(5);
                continue;
            }

            foreach ($recipes as $r) {
                $title = trim($r['title'] ?? '');
                if (!$title || in_array(strtolower($title), $existingTitles)) {
                    continue;
                }

                try {
                    DB::transaction(function () use ($r, $tag) {
                        $recipe = Recipe::create([
                            'user_id'           => null,
                            'title'             => $r['title'],
                            'description'       => $r['description'] ?? null,
                            'source'            => 'ai_generated',
                            'budget_tag'        => $tag,
                            'estimated_cost'    => (float) ($r['estimated_cost'] ?? 0),
                            'servings'          => (int) ($r['servings'] ?? 4),
                            'prep_time_minutes' => (int) ($r['prep_time_minutes'] ?? 10),
                            'cook_time_minutes' => (int) ($r['cook_time_minutes'] ?? 30),
                            'tags'              => $r['tags'] ?? [],
                            'dietary_flags'     => $r['dietary_flags'] ?? [],
                            'steps'             => $r['steps'] ?? [],
                            'tips'              => $r['tips'] ?? [],
                            'is_premium_only'   => false,
                            'is_published'      => true,
                        ]);

                        foreach ($r['ingredients'] ?? [] as $idx => $ing) {
                            RecipeIngredient::create([
                                'recipe_id'       => $recipe->id,
                                'name'            => $ing['name'],
                                'quantity'        => (string) ($ing['quantity'] ?? '1'),
                                'unit'            => $ing['unit'] ?? 'pcs',
                                'estimated_price' => (float) ($ing['estimated_price'] ?? 0),
                                'notes'           => $ing['notes'] ?? null,
                                'sort_order'      => $idx,
                            ]);
                        }
                    });

                    $existingTitles[] = strtolower($title);
                    $saved++;
                    $this->line("    + {$title}");
                } catch (\Throwable $e) {
                    $this->warn("    ! Skip '{$title}': " . $e->getMessage());
                }
            }

            // Brief pause between batches to avoid rate limits
            if ($i < $batches - 1) {
                sleep(2);
            }
        }

        return $saved;
    }

    private function callClaude(string $tag, array $meta, int $count, array $skip): array
    {
        $skipNote = !empty($skip)
            ? 'Do NOT repeat these dish titles (already in DB): ' . implode(', ', array_slice($skip, -20)) . '.'
            : '';

        $system = <<<SYSTEM
        You are a Filipino culinary expert specializing in home cooking for Filipino families.
        You know all traditional Filipino dishes from Luzon, Visayas, and Mindanao.
        All prices are in Philippine Peso (PHP).
        Use ingredients easily available in Philippine wet markets and supermarkets.
        Always respond with ONLY valid JSON — no text before or after.
        SYSTEM;

        $prompt = <<<PROMPT
        Generate exactly {$count} unique Filipino recipes for the {$meta['label']} budget tier.
        Budget range per dish (serves 4–6 people): {$meta['range']}.
        {$meta['description']}.
        {$skipNote}

        Include a variety of: main dishes (ulam), rice variants (sinangag, fried rice), soups (sabaw), and snacks (meryenda).
        Use classic Filipino dishes: adobo, sinigang, tinola, nilaga, pinakbet, pakbet, ginisang monggo, tortang talong,
        lumpiang gulay, inihaw, kaldereta, menudo, afritada, kare-kare, bistek, lechon kawali, crispy pata, etc.
        Also include regional variants and simple everyday dishes.

        Return a JSON array of exactly {$count} objects, each with this exact format:
        [
          {
            "title": "Chicken Adobo",
            "description": "Isang klasikong Filipino dish na may suka at toyo.",
            "estimated_cost": 180.00,
            "servings": 4,
            "prep_time_minutes": 10,
            "cook_time_minutes": 35,
            "tags": ["adobo", "chicken", "main dish"],
            "dietary_flags": ["halal"],
            "ingredients": [
              {"name": "Manok", "quantity": "1", "unit": "kg", "estimated_price": 140, "notes": null},
              {"name": "Toyo", "quantity": "1/4", "unit": "cup", "estimated_price": 10, "notes": null}
            ],
            "steps": [
              "Hiwain ang manok sa serving pieces.",
              "Lutuin sa toyo, suka, bawang, at paminta ng 30 minuto."
            ],
            "tips": ["Mas masarap kapag pinakuluan ng dalawang beses.", "Pwedeng mag-marinate ng overnight."]
          }
        ]

        dietary_flags can include: vegetarian, vegan, halal, gluten-free, or empty array.
        steps should be in Tagalog (short imperative sentences, 3–8 steps).
        tips should be in Tagalog (2–3 practical cooking tips).
        PROMPT;

        $response = $this->claude->messages->create(
            maxTokens: 4096,
            messages: [['role' => 'user', 'content' => $prompt]],
            model:     config('services.anthropic.model', 'claude-sonnet-4-6'),
            system:    $system,
        );

        $raw = $response->content[0]->text ?? '[]';

        // Strip markdown fences
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/\s*```$/m', '', $raw);
        $raw = trim($raw);

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \RuntimeException('Invalid JSON from Claude: ' . json_last_error_msg());
        }

        return $data;
    }
}
