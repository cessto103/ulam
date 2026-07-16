<?php

namespace App\Services;

use Anthropic\Client as AnthropicClient;
use App\Models\MealPlan;
use App\Models\MealPlanIngredient;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MealPlanService
{
    private AnthropicClient $client;

    public function __construct()
    {
        $this->client = new AnthropicClient(
            apiKey: config('services.anthropic.key'),
        );
    }

    public function generate(User $user, float $dailyBudget, ?string $preferences = null): MealPlan
    {
        if (!$user->canGenerateAiMealPlan()) {
            throw new \RuntimeException('AI meal plans are a Premium-only feature.');
        }

        $dietary = $user->dietary_preferences ?? [];
        $householdSize = $user->household_size ?? 4;
        $budgetPerPerson = round($dailyBudget / $householdSize, 2);

        $system = <<<SYSTEM
        Ikaw ay isang Pilipinong nutritionist at home cook na eksperto sa budget-friendly na pagkain.
        Gumagamit ka ng mga sangkap na madaling makita sa palengke ng Pilipinas.
        Palaging isulat ang presyo sa PHP (Philippine Peso).
        Ang lahat ng rekomendasyon ay para sa mga Pilipino sa Luzon, Visayas, at Mindanao.
        Sumagot LAGI sa valid JSON format lamang, walang ibang teksto bago o pagkatapos.
        SYSTEM;

        $dietaryNote = !empty($dietary) ? 'Dietary restrictions: ' . implode(', ', $dietary) . '.' : '';
        $prefNote = $preferences ? "Additional preferences: {$preferences}." : '';

        $prompt = <<<PROMPT
        Gumawa ng daily meal plan para sa isang pamilyang may {$householdSize} katao.
        Kabuuang budget para sa buong araw: ₱{$dailyBudget} (₱{$budgetPerPerson} bawat tao).
        {$dietaryNote}
        {$prefNote}

        Kasama ang: almusal (breakfast), tanghalian (lunch), meryenda (afternoon snack), at hapunan (dinner).
        Gumamit ng mga lutong Pilipino tulad ng sinangag, tapsilog, sinigang, adobo, tinola, pinakbet, atbp.
        Siguraduhing ang kabuuang gastos ay hindi lalampas sa ₱{$dailyBudget}.

        Ibalik ang JSON na may eksaktong format na ito:
        {
          "total_estimated_cost": 0.00,
          "meals": [
            {
              "meal_type": "almusal",
              "dish_name": "pangalan ng ulam",
              "description": "maikling paglalarawan",
              "estimated_cost": 0.00,
              "servings": {$householdSize},
              "prep_time_minutes": 10,
              "cook_time_minutes": 20,
              "difficulty": "easy",
              "tags": ["manok", "mabilis"],
              "ingredients": [
                {"name": "sangkap", "quantity": "dami", "unit": "yunit", "estimated_price": 0.00}
              ],
              "steps": [
                "Hakbang 1 sa pagluluto"
              ],
              "tips": [
                "Opsyonal na tip para sa mas masarap na resulta"
              ]
            }
          ]
        }

        Ang "difficulty" ay dapat "easy", "medium", o "hard" lamang.
        Ang "steps" ay dapat malinaw, sunud-sunod na hakbang sa pagluluto, hindi maaaring walang laman.
        Ang "tips" ay maaaring walang laman na array kung wala talagang opsyonal na tip.
        PROMPT;

        $response = $this->client->messages->create(
            maxTokens: 4096,
            messages: [['role' => 'user', 'content' => $prompt]],
            model: config('services.anthropic.model', 'claude-sonnet-4-6'),
            system: $system,
            temperature: 0.7,
        );

        $rawText = $response->content[0]->text ?? '';
        $data = $this->parseJson($rawText);

        return DB::transaction(function () use ($user, $data, $response) {
            $mealPlan = MealPlan::create([
                'user_id' => $user->id,
                'plan_date' => now()->toDateString(),
                'source' => 'ai_generated',
                'total_estimated_cost' => $data['total_estimated_cost'] ?? 0,
                'ai_prompt_tokens' => $response->usage->inputTokens ?? 0,
                'ai_completion_tokens' => $response->usage->outputTokens ?? 0,
            ]);

            foreach ($data['meals'] ?? [] as $meal) {
                $ingredients = $meal['ingredients'] ?? [];
                $cost = (float) ($meal['estimated_cost'] ?? 0);

                // Each AI-generated dish also becomes a real, ownable Recipe —
                // same page, ratings, votes, and comments as any other recipe,
                // deletable by the user like anything else they own.
                $recipe = Recipe::create([
                    'user_id' => $user->id,
                    'title' => $meal['dish_name'],
                    'description' => $meal['description'] ?? null,
                    'source' => 'ai_generated',
                    'budget_tag' => $this->budgetTagFor($cost),
                    'estimated_cost' => $cost,
                    'servings' => $meal['servings'] ?? 4,
                    'prep_time_minutes' => $meal['prep_time_minutes'] ?? 0,
                    'cook_time_minutes' => $meal['cook_time_minutes'] ?? 0,
                    'difficulty' => in_array($meal['difficulty'] ?? null, ['easy', 'medium', 'hard'], true)
                        ? $meal['difficulty']
                        : 'easy',
                    'tags' => array_values($meal['tags'] ?? []),
                    'steps' => array_values($meal['steps'] ?? []),
                    'tips' => array_values($meal['tips'] ?? []),
                    'is_published' => true,
                    'is_premium_only' => false,
                ]);

                foreach ($ingredients as $i => $ing) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'name' => $ing['name'],
                        'quantity' => $ing['quantity'],
                        'unit' => $ing['unit'],
                        'estimated_price' => $ing['estimated_price'] ?? 0,
                        'sort_order' => $i,
                    ]);
                }

                $item = MealPlanItem::create([
                    'meal_plan_id' => $mealPlan->id,
                    'meal_type' => $meal['meal_type'],
                    'dish_name' => $meal['dish_name'],
                    'description' => $meal['description'] ?? null,
                    'estimated_cost' => $cost,
                    'servings' => $meal['servings'] ?? 4,
                    'recipe_id' => $recipe->id,
                ]);

                foreach ($ingredients as $ing) {
                    MealPlanIngredient::create([
                        'meal_plan_item_id' => $item->id,
                        'name' => $ing['name'],
                        'quantity' => $ing['quantity'],
                        'unit' => $ing['unit'],
                        'estimated_price' => $ing['estimated_price'] ?? 0,
                    ]);
                }
            }

            $user->increment('ai_meal_plans_used_this_month');

            return $mealPlan->load('items.ingredients', 'items.recipe');
        });
    }

    /** Buckets a raw peso amount into the same budget_tag scale the app already renders. */
    private function budgetTagFor(float $cost): string
    {
        return match (true) {
            $cost <= 100 => 'budget_100',
            $cost <= 200 => 'budget_200',
            $cost <= 400 => 'budget_400',
            $cost <= 600 => 'budget_600',
            $cost <= 800 => 'budget_800',
            $cost <= 1000 => 'budget_1000',
            default => 'budget_1000plus',
        };
    }

    private function parseJson(string $text): array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('MealPlanService JSON parse error', ['text' => $text]);
            throw new \RuntimeException('Hindi ma-parse ang meal plan. Subukan ulit.');
        }

        return $data;
    }
}
