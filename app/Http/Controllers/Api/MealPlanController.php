<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealPlan;
use App\Models\MealPlanIngredient;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Services\MealPlanService;
use App\Services\XpService;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    public function __construct(private MealPlanService $mealPlanService)
    {
    }

    public function today(Request $request)
    {
        $date = $request->query('date') ?? now()->toDateString();

        $mealPlan = MealPlan::where('user_id', $request->user()->id)
            ->whereDate('plan_date', $date)
            ->with('items.ingredients')
            ->latest()
            ->first();

        if (!$mealPlan) {
            return response()->json(['meal_plan' => null]);
        }

        return response()->json(['meal_plan' => $mealPlan]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'preferences' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $budget = $user->currentBudget;

        if (!$budget) {
            return response()->json(['message' => 'I-setup muna ang iyong budget bago mag-generate ng meal plan.'], 422);
        }

        try {
            $mealPlan = $this->mealPlanService->generate(
                $user,
                (float) $budget->daily_food_budget,
                $request->preferences,
            );

            app(XpService::class)->award($user, 20, 'generate_meal_plan', $mealPlan);

            return response()->json(['meal_plan' => $mealPlan], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'date'           => ['required', 'date_format:Y-m-d'],
            'meal_type'      => ['required', 'in:almusal,tanghalian,meryenda,hapunan,iba pa'],
            'recipe_id'      => ['required', 'integer', 'exists:recipes,id'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user   = $request->user();
        $recipe = Recipe::findOrFail($data['recipe_id']);

        // Find or create today's meal plan for the given date
        $plan = MealPlan::firstOrCreate(
            ['user_id' => $user->id, 'plan_date' => $data['date']],
            ['source' => 'manual', 'total_estimated_cost' => 0],
        );

        // Reject duplicate recipe in the same meal slot
        $alreadyAdded = $plan->items()
            ->where('recipe_id', $recipe->id)
            ->where('meal_type', $data['meal_type'])
            ->exists();

        if ($alreadyAdded) {
            return response()->json(['message' => 'This recipe is already added to this meal slot.'], 422);
        }

        $item = MealPlanItem::create([
            'meal_plan_id'   => $plan->id,
            'recipe_id'      => $recipe->id,
            'meal_type'      => $data['meal_type'],
            'dish_name'      => $recipe->title,
            'description'    => $recipe->description ?? '',
            'estimated_cost' => $data['estimated_cost'] ?? $recipe->estimated_cost ?? 0,
            'servings'       => $recipe->servings ?? 1,
            'sort_order'     => match($data['meal_type']) {
                'almusal'    => 1,
                'tanghalian' => 2,
                'meryenda'   => 3,
                'hapunan'    => 4,
                default      => 5,
            },
        ]);

        // Copy recipe ingredients into meal plan ingredients
        $recipe->load('ingredients');
        foreach ($recipe->ingredients as $ing) {
            MealPlanIngredient::create([
                'meal_plan_item_id' => $item->id,
                'name'              => $ing->name,
                'quantity'          => $ing->quantity ?? '',
                'unit'              => $ing->unit ?? '',
                'estimated_price'   => $ing->estimated_price ?? 0,
            ]);
        }

        // Recalculate total
        $plan->total_estimated_cost = $plan->items()->sum('estimated_cost');
        $plan->save();

        return response()->json(['meal_plan_item' => $item->load('ingredients')], 201);
    }

    public function removeItem(Request $request, int $itemId)
    {
        $user = $request->user();

        $item = MealPlanItem::whereHas('mealPlan', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($itemId);

        $plan = $item->mealPlan;
        $item->delete();

        $plan->total_estimated_cost = $plan->items()->sum('estimated_cost');
        $plan->save();

        return response()->json([
            'total_estimated_cost' => $plan->total_estimated_cost,
        ]);
    }

    public function datesWithPlans(Request $request)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end'   => ['required', 'date', 'after_or_equal:start'],
        ]);

        $dates = MealPlan::where('user_id', $request->user()->id)
            ->whereBetween('plan_date', [$request->start, $request->end])
            ->pluck('plan_date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->values();

        return response()->json(['dates' => $dates]);
    }

    public function regenerate(Request $request)
    {
        $request->validate([
            'preferences' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $budget = $user->currentBudget;

        if (!$budget) {
            return response()->json(['message' => 'I-setup muna ang iyong budget.'], 422);
        }

        // Delete today's existing plan
        MealPlan::where('user_id', $user->id)
            ->whereDate('plan_date', now()->toDateString())
            ->delete();

        // Decrement count since we're replacing, not adding
        if (!$user->isPremium() && $user->ai_meal_plans_used_this_month > 0) {
            $user->decrement('ai_meal_plans_used_this_month');
        }

        try {
            $mealPlan = $this->mealPlanService->generate(
                $user,
                (float) $budget->daily_food_budget,
                $request->preferences,
            );

            return response()->json(['meal_plan' => $mealPlan], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
