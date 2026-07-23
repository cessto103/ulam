<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\BudgetPeriod;
use App\Models\MealPlan;
use App\Models\MealPlanIngredient;
use App\Models\MealPlanItem;
use App\Models\Recipe;
use App\Models\User;
use App\Services\MealPlanService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    public function __construct(private MealPlanService $mealPlanService)
    {
    }

    // Cost kill switch — AI generation calls Claude per request, so this can
    // be flipped off from AppSettings without a deploy if spend needs to stop.
    private function aiGenerationDisabled(): bool
    {
        return AppSetting::get('ai_meal_plans_enabled', '1') !== '1';
    }

    // 7-Day Meal Planning (Premium): a target date must fall within today..+7,
    // and any date other than today requires Premium. Returns an error
    // response to short-circuit on, or null when the date is allowed.
    private function validateDateWindow(string $date, User $user): ?JsonResponse
    {
        $today = now()->toDateString();
        $max = now()->addDays(7)->toDateString();

        if ($date < $today || $date > $max) {
            return response()->json(['message' => 'Date must be between today and 7 days from now.'], 422);
        }

        if ($date !== $today && !$user->isPremium()) {
            return response()->json([
                'message' => '7-Day Meal Planning is a Premium feature.',
                'premium_required' => true,
            ], 403);
        }

        return null;
    }

    public function today(Request $request)
    {
        $date = $request->query('date') ?? now()->toDateString();

        $mealPlan = MealPlan::where('user_id', $request->user()->id)
            ->whereDate('plan_date', $date)
            ->with(['items.ingredients', 'items.recipe:id,image_url,image_urls'])
            ->latest()
            ->first();

        if (!$mealPlan) {
            return response()->json(['meal_plan' => null]);
        }

        return response()->json(['meal_plan' => $mealPlan]);
    }

    public function generate(Request $request)
    {
        if ($this->aiGenerationDisabled()) {
            return response()->json([
                'message' => 'AI meal plan generation is temporarily unavailable. Please check back soon!',
                'ai_disabled' => true,
            ], 503);
        }

        $request->validate([
            'preferences' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = $request->user();
        $date = $request->input('date') ?? now()->toDateString();

        if ($windowError = $this->validateDateWindow($date, $user)) {
            return $windowError;
        }

        $budget = BudgetPeriod::forUserAndDate($user->id, $date);

        if (!$budget) {
            return response()->json([
                'message' => 'Please set up your budget before generating a meal plan.',
                'no_budget' => true,
            ], 422);
        }

        try {
            $mealPlan = $this->mealPlanService->generate(
                $user,
                (float) $budget->daily_food_budget,
                $request->preferences,
                $date,
            );

            // Once/day, not per-generation -- Premium users are intentionally
            // allowed unlimited generations (ai_plans_remaining is null for
            // them by design), so nothing else bounds how many times this
            // could be called and each one paid out XP unconditionally.
            $reward = app(XpService::class)->awardOncePerDay($user, 20, 'generate_meal_plan', $mealPlan);

            return response()->json([
                'meal_plan'        => $mealPlan,
                'xp_earned'        => $reward['xp_awarded'] ?? 0,
                'leveled_up'       => $reward['leveled_up'] ?? false,
                'new_level'        => $reward['new_level'] ?? null,
                'new_achievements' => $reward['new_achievements'] ?? [],
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message'        => $e->getMessage(),
                'quota_exceeded' => ! $user->canGenerateAiMealPlan(),
            ], 422);
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

        // 7-Day Meal Planning (Premium): manually choosing a recipe for a
        // future date is gated the same as AI generation. Past/today dates
        // (e.g. editing spending history) are unaffected.
        if ($data['date'] > now()->toDateString() && !$user->isPremium()) {
            return response()->json([
                'message' => '7-Day Meal Planning is a Premium feature.',
                'premium_required' => true,
            ], 403);
        }

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
        if ($this->aiGenerationDisabled()) {
            return response()->json([
                'message' => 'AI meal plan generation is temporarily unavailable. Please check back soon!',
                'ai_disabled' => true,
            ], 503);
        }

        $request->validate([
            'preferences' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = $request->user();
        $date = $request->input('date') ?? now()->toDateString();

        if ($windowError = $this->validateDateWindow($date, $user)) {
            return $windowError;
        }

        $budget = BudgetPeriod::forUserAndDate($user->id, $date);

        if (!$budget) {
            return response()->json([
                'message' => 'Please set up your budget first.',
                'no_budget' => true,
            ], 422);
        }

        if (!$user->canGenerateAiMealPlan()) {
            return response()->json([
                'message'        => 'AI meal plans are a Premium-only feature.',
                'quota_exceeded' => true,
            ], 422);
        }

        // Delete the existing plan for that date — only reached once we know
        // a replacement is actually allowed, so a blocked user never loses
        // their existing plan for nothing.
        MealPlan::where('user_id', $user->id)
            ->whereDate('plan_date', $date)
            ->delete();

        try {
            $mealPlan = $this->mealPlanService->generate(
                $user,
                (float) $budget->daily_food_budget,
                $request->preferences,
                $date,
            );

            return response()->json(['meal_plan' => $mealPlan], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message'        => $e->getMessage(),
                'quota_exceeded' => ! $user->canGenerateAiMealPlan(),
            ], 422);
        }
    }
}
