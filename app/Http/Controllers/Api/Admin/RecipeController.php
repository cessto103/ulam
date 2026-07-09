<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::with('user:id,name');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('budget_tag')) {
            $query->where('budget_tag', $request->string('budget_tag'));
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $recipe = Recipe::with(['user:id,name', 'ingredients'])->findOrFail($id);

        return response()->json(['recipe' => $recipe]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $recipe = Recipe::create($validated);

        return response()->json(['recipe' => $recipe], 201);
    }

    public function update(Request $request, int $id)
    {
        $recipe = Recipe::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));

        $recipe->update($validated);

        return response()->json(['recipe' => $recipe->fresh()]);
    }

    public function destroy(int $id)
    {
        Recipe::findOrFail($id)->delete();

        return response()->json(['message' => 'Recipe deleted.']);
    }

    public function ingredients(int $id)
    {
        $recipe = Recipe::findOrFail($id);

        return response()->json(['ingredients' => $recipe->ingredients]);
    }

    public function addIngredient(Request $request, int $id)
    {
        $recipe = Recipe::findOrFail($id);
        $validated = $request->validate($this->ingredientRules());
        $validated['recipe_id'] = $recipe->id;

        $ingredient = RecipeIngredient::create($validated);

        return response()->json(['ingredient' => $ingredient], 201);
    }

    public function updateIngredient(Request $request, int $id, int $ingredientId)
    {
        $ingredient = RecipeIngredient::where('recipe_id', $id)->findOrFail($ingredientId);
        $validated = $request->validate($this->ingredientRules(sometimes: true));

        $ingredient->update($validated);

        return response()->json(['ingredient' => $ingredient->fresh()]);
    }

    public function destroyIngredient(int $id, int $ingredientId)
    {
        RecipeIngredient::where('recipe_id', $id)->findOrFail($ingredientId)->delete();

        return response()->json(['message' => 'Ingredient deleted.']);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => [$req, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'source' => [$req, 'in:ai_generated,community,admin,official'],
            'budget_tag' => [$req, 'in:budget_100,budget_200,budget_400,budget_400plus'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'servings' => ['nullable', 'integer', 'min:1'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'difficulty' => ['nullable', 'in:madali,katamtaman,mahirap'],
            'steps' => ['nullable', 'array'],
            'tips' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'dietary_flags' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_urls' => ['nullable', 'array'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'collage_style' => ['nullable', 'string', 'max:50'],
            'gradient_key' => ['nullable', 'string', 'max:50'],
            'font_key' => ['nullable', 'string', 'max:50'],
            'is_published' => ['sometimes', 'boolean'],
            'is_premium_only' => ['sometimes', 'boolean'],
        ];
    }

    private function ingredientRules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'name' => [$req, 'string', 'max:255'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'estimated_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
