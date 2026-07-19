<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\StaplePrice;
use App\Models\User;
use Illuminate\Support\Collection;

class ShoppingListService
{
    /**
     * Copy today's meal plan ingredients into a fresh daily list, applying
     * the tingi/staple price swap per line. The meal plan itself is never
     * written — the swap only affects the generated shopping list.
     */
    public function createDailyFromMealPlan(User $owner, MealPlan $plan, string $date): ShoppingList
    {
        $list = ShoppingList::create([
            'owner_id'     => $owner->id,
            'type'         => 'daily',
            'title'        => 'Shopping List ' . $date,
            'list_date'    => $date,
            'meal_plan_id' => $plan->id,
        ]);

        $staples = StaplePrice::active()->get();
        $sort = 0;

        foreach ($plan->items as $item) {
            foreach ($item->ingredients as $ingredient) {
                $line = $this->applyStaple([
                    'name'      => $ingredient->name,
                    'quantity'  => $ingredient->quantity,
                    'unit'      => $ingredient->unit,
                    'est_price' => (float) $ingredient->estimated_price,
                ], $staples);

                $list->items()->create([
                    ...$line,
                    'meal_type' => $item->meal_type,
                    'dish_name' => $item->dish_name,
                    'added_by'  => $owner->id,
                    'sort_order' => $sort++,
                ]);
            }
        }

        return $list;
    }

    /** Standalone event list, optionally seeded from a recipe's ingredients. */
    public function createEventFromRecipe(User $owner, string $title, ?Recipe $recipe): ShoppingList
    {
        $list = ShoppingList::create([
            'owner_id'         => $owner->id,
            'type'             => 'event',
            'title'            => $title,
            'source_recipe_id' => $recipe?->id,
        ]);

        if ($recipe) {
            $staples = StaplePrice::active()->get();
            $sort = 0;
            foreach ($recipe->ingredients as $ingredient) {
                $line = $this->applyStaple([
                    'name'      => $ingredient->name,
                    'quantity'  => $ingredient->quantity,
                    'unit'      => $ingredient->unit,
                    'est_price' => (float) $ingredient->estimated_price,
                ], $staples);

                $list->items()->create([
                    ...$line,
                    'dish_name' => $recipe->title,
                    'added_by'  => $owner->id,
                    'sort_order' => $sort++,
                ]);
            }
        }

        return $list;
    }

    /**
     * Tingi/staple swap for one generated line. Recipes cost staples
     * proportionally (2 tbsp of toyo = a few pesos) but the smallest thing
     * anyone can BUY is a sachet/takal — so a matching line gets the staple's
     * purchasable unit and price, keeping the recipe's amount as a note
     * ("kailangan: 2 tbsp"). Matching: exact name first, then whole-word
     * contains (so staple "asin" matches "asin (salt)" but never "kasim"),
     * longest staple name first so "brown sugar" wins over "sugar".
     *
     * @param array{name: string, quantity: ?string, unit: ?string, est_price: float} $ingredient
     * @return array{name: string, quantity: ?string, unit: ?string, est_price: float, needed_note: ?string}
     */
    public function applyStaple(array $ingredient, Collection $staples): array
    {
        $name = mb_strtolower(trim($ingredient['name']));

        $ordered = $staples->sortByDesc(fn ($s) => mb_strlen($s->item_name));

        $match = $ordered->first(function ($s) use ($name) {
            $staple = mb_strtolower(trim($s->item_name));
            if ($staple === $name) {
                return true;
            }
            if (mb_strlen($staple) < 3) {
                return false;
            }
            return (bool) preg_match('/\b' . preg_quote($staple, '/') . '\b/iu', $name);
        });

        if (! $match) {
            return [...$ingredient, 'needed_note' => null];
        }

        $neededAmount = trim(($ingredient['quantity'] ?? '') . ' ' . ($ingredient['unit'] ?? ''));

        return [
            'name'        => $ingredient['name'],
            'quantity'    => '1',
            'unit'        => $match->unit,
            'est_price'   => (float) $match->price,
            'needed_note' => $neededAmount !== '' ? "kailangan: {$neededAmount}" : null,
        ];
    }
}
