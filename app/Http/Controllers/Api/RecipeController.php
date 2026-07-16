<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\ContentView;
use App\Models\Post;
use App\Models\Recipe;
use App\Models\RecipeBook;
use App\Models\RecipeIngredient;
use App\Models\RecipeRating;
use App\Models\RecipeReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Recipe::with(['ingredients', 'user:id,name,username'])
            ->where(function ($q) use ($user) {
                $q->where('is_published', true)->orWhere('user_id', $user->id);
            })
            ->selectRaw(
                'recipes.*,
                 EXISTS(SELECT 1 FROM recipe_book WHERE recipe_book.recipe_id = recipes.id AND recipe_book.user_id = ?) as is_saved,
                 (recipes.user_id = ?) as is_mine,
                 EXISTS(SELECT 1 FROM ad_boosts WHERE ad_boosts.boostable_type = ? AND ad_boosts.boostable_id = recipes.id AND ad_boosts.status = ? AND ad_boosts.expires_at > ?) as is_boosted,
                 (SELECT COUNT(*) FROM content_views WHERE content_views.viewable_type = ? AND content_views.viewable_id = recipes.id) as views_count,
                 (SELECT COUNT(*) FROM content_views WHERE content_views.viewable_type = ? AND content_views.viewable_id = recipes.id AND content_views.viewed_at >= ?) as views_7d',
                [$user->id, $user->id, Recipe::class, 'active', now(), Recipe::class, Recipe::class, now()->subDays(7)]
            );

        if (! $user->isPremium()) {
            $query->where(function ($q) use ($user) {
                $q->where('is_premium_only', false)->orWhere('user_id', $user->id);
            });
        }

        if ($request->has('budget_tag')) {
            $query->where('budget_tag', $request->budget_tag);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            // Title, ingredients, and tags — "manok" should find Tinola even
            // when the title doesn't contain the word.
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('tags', 'like', "%{$s}%")
                  ->orWhereHas('ingredients', fn ($iq) => $iq->where('name', 'like', "%{$s}%"));
            });
        }

        // Popularity = boosted first, then real views in the last 7 days,
        // then all-time saves as the tiebreaker for content without views yet.
        $recipes = $query->orderByDesc('is_boosted')
            ->orderByDesc('views_7d')
            ->orderByDesc('save_count')
            ->paginate(50);

        $recipes->getCollection()->transform(function ($r) {
            $r->is_saved    = (bool) $r->is_saved;
            $r->is_mine     = (bool) $r->is_mine;
            $r->is_boosted  = (bool) $r->is_boosted;
            $r->views_count = (int) $r->views_count;
            $r->views_7d    = (int) $r->views_7d;
            return $r;
        });

        return response()->json($recipes);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $recipe = Recipe::with(['ingredients', 'user:id,name,username'])->findOrFail($id);

        if ($recipe->is_premium_only && !$user->isPremium()) {
            return response()->json(['message' => 'Premium only recipe. Upgrade to access.'], 403);
        }

        ContentView::log($recipe, $user, $recipe->user_id);
        $recipe->views_count = $recipe->contentViews()->count();

        $isSaved = RecipeBook::where('user_id', $user->id)
            ->where('recipe_id', $id)
            ->exists();

        $myRating   = RecipeRating::where('user_id', $user->id)->where('recipe_id', $id)->value('rating');
        $myReaction = RecipeReaction::where('user_id', $user->id)->where('recipe_id', $id)->value('type');

        $sharedBy = Post::with('user:id,name,username,avatar')
            ->where('recipe_id', $id)
            ->where('post_type', 'recipe_share')
            ->latest()
            ->take(5)
            ->get(['id', 'user_id', 'created_at']);

        $isBoosted = AdBoost::where('boostable_type', Recipe::class)
            ->where('boostable_id', $id)
            ->active()
            ->exists();

        return response()->json([
            'recipe'      => $recipe,
            'is_saved'    => $isSaved,
            'is_mine'     => $recipe->user_id === $user->id,
            'is_boosted'  => $isBoosted,
            'my_rating'   => $myRating,
            'my_reaction' => $myReaction,
            'shared_by'   => $sharedBy,
        ]);
    }

    public function sharers(Request $request, int $id)
    {
        Recipe::findOrFail($id);

        $sharers = Post::with('user:id,name,username,avatar')
            ->where('recipe_id', $id)
            ->where('post_type', 'recipe_share')
            ->latest()
            ->paginate(30);

        return response()->json($sharers);
    }

    public function rate(Request $request, int $id)
    {
        $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        $user   = $request->user();
        $recipe = Recipe::findOrFail($id);

        RecipeRating::updateOrCreate(
            ['user_id' => $user->id, 'recipe_id' => $id],
            ['rating' => $request->rating]
        );

        // Recompute denormalised aggregates
        $agg = RecipeRating::where('recipe_id', $id)->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();
        $recipe->update([
            'average_rating' => round($agg->avg_r, 2),
            'ratings_count'  => $agg->cnt,
        ]);

        return response()->json([
            'average_rating' => $recipe->fresh()->average_rating,
            'ratings_count'  => $recipe->fresh()->ratings_count,
            'my_rating'      => $request->rating,
        ]);
    }

    public function saveToBook(Request $request, int $id)
    {
        $user = $request->user();
        Recipe::findOrFail($id);

        $existing = RecipeBook::where('user_id', $user->id)->where('recipe_id', $id)->first();

        if ($existing) {
            $existing->delete();
            Recipe::where('id', $id)->decrement('save_count');
            return response()->json(['saved' => false]);
        }

        RecipeBook::create(['user_id' => $user->id, 'recipe_id' => $id]);
        Recipe::where('id', $id)->increment('save_count');
        return response()->json(['saved' => true]);
    }

    public function react(Request $request, int $id)
    {
        $request->validate(['type' => ['required', 'in:up,down']]);

        $user   = $request->user();
        $recipe = Recipe::findOrFail($id);
        $type   = $request->input('type');

        $col      = fn (string $t) => $t === 'up' ? 'vote_up_count' : 'vote_down_count';
        $existing = RecipeReaction::where('user_id', $user->id)->where('recipe_id', $id)->first();

        if ($existing) {
            if ($existing->type === $type) {
                // Toggle off same reaction
                $existing->delete();
                $recipe->decrement($col($type));
                return response()->json(['my_reaction' => null]);
            }
            // Switch reaction (e.g. up → down)
            $old = $existing->type;
            $existing->update(['type' => $type]);
            $recipe->decrement($col($old));
            $recipe->increment($col($type));
            return response()->json(['my_reaction' => $type]);
        }

        RecipeReaction::create(['user_id' => $user->id, 'recipe_id' => $id, 'type' => $type]);
        $recipe->increment($col($type));
        return response()->json(['my_reaction' => $type]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'             => ['required', 'string', 'max:120'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'servings'          => ['required', 'integer', 'min:1', 'max:20'],
            'prep_time_minutes' => ['required', 'integer', 'min:0'],
            'cook_time_minutes' => ['required', 'integer', 'min:0'],
            'difficulty'        => ['required', 'in:easy,medium,hard'],
            'budget_tag'        => ['required', 'string', 'max:30'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:40'],
            'steps'             => ['required', 'array', 'min:1'],
            'steps.*'           => ['string', 'max:500'],
            'ingredients'       => ['required', 'array', 'min:1'],
            'ingredients.*.name'  => ['required', 'string', 'max:80'],
            'ingredients.*.qty'   => ['required', 'string', 'max:80'],
            'ingredients.*.price' => ['required', 'numeric', 'min:0'],
            'images'            => ['nullable', 'array', 'max:3'],
            'images.*'          => ['image', 'max:8192'],
            'collage_style'     => ['nullable', 'string', 'in:split,circle_right,full,three_col,top_bottom,gradient'],
            'gradient_key'      => ['nullable', 'string', 'in:grad_a,grad_b,grad_c,grad_d,grad_e,grad_f,grad_g,grad_h'],
            'font_key'          => ['nullable', 'string', 'in:baloo,dancing,pacifico,satisfy,lobster'],
            'youtube_url'       => ['nullable', 'url', 'max:255'],
            'tips'              => ['nullable', 'array'],
            'tips.*'            => ['string', 'max:300'],
        ]);

        $user = $request->user();

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path         = $file->store("recipes/{$user->id}", 'public');
                $imagePaths[] = url(Storage::url($path));
            }
        }

        $ingredients = $request->input('ingredients', []);
        $totalCost   = collect($ingredients)->sum('price');

        $recipe = Recipe::create([
            'user_id'           => $user->id,
            'title'             => $request->title,
            'description'       => $request->description,
            'source'            => 'community',
            'budget_tag'        => $request->budget_tag,
            'estimated_cost'    => $totalCost,
            'servings'          => $request->servings,
            'prep_time_minutes' => $request->prep_time_minutes,
            'cook_time_minutes' => $request->cook_time_minutes,
            'difficulty'        => $request->difficulty,
            'tags'              => array_values($request->input('tags', [])),
            'steps'             => array_values($request->input('steps', [])),
            'is_published'      => false,
            'is_premium_only'   => false,
            'image_url'         => $imagePaths[0] ?? null,
            'image_urls'        => count($imagePaths) > 0 ? $imagePaths : null,
            'collage_style'     => $request->input('collage_style', 'gradient'),
            'gradient_key'      => $request->input('gradient_key', 'grad_a'),
            'font_key'          => $request->input('font_key', 'baloo'),
            'youtube_url'       => $request->input('youtube_url') ?: null,
            'tips'              => array_values($request->input('tips', [])),
        ]);

        foreach ($ingredients as $i => $ing) {
            RecipeIngredient::create([
                'recipe_id'       => $recipe->id,
                'name'            => $ing['name'],
                'quantity'        => $ing['qty'],
                'unit'            => '',
                'estimated_price' => $ing['price'],
                'sort_order'      => $i,
            ]);
        }

        foreach (($imagePaths ?: []) as $img) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($img, 'recipe.images', $recipe->id);
        }

        return response()->json(['recipe' => $recipe->load('ingredients')], 201);
    }

    public function update(Request $request, int $id)
    {
        $user   = $request->user();
        $recipe = Recipe::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'title'             => ['required', 'string', 'max:120'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'servings'          => ['required', 'integer', 'min:1', 'max:20'],
            'prep_time_minutes' => ['required', 'integer', 'min:0'],
            'cook_time_minutes' => ['required', 'integer', 'min:0'],
            'difficulty'        => ['required', 'in:easy,medium,hard'],
            'budget_tag'        => ['required', 'string', 'max:30'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:40'],
            'steps'             => ['required', 'array', 'min:1'],
            'steps.*'           => ['string', 'max:500'],
            'ingredients'       => ['required', 'array', 'min:1'],
            'ingredients.*.name'  => ['required', 'string', 'max:80'],
            'ingredients.*.qty'   => ['required', 'string', 'max:80'],
            'ingredients.*.price' => ['required', 'numeric', 'min:0'],
            'existing_images'   => ['nullable', 'array', 'max:3'],
            'existing_images.*' => ['string', 'url'],
            'images'            => ['nullable', 'array', 'max:3'],
            'images.*'          => ['image', 'max:8192'],
            'collage_style'     => ['nullable', 'string', 'in:split,circle_right,full,three_col,top_bottom,gradient'],
            'gradient_key'      => ['nullable', 'string', 'in:grad_a,grad_b,grad_c,grad_d,grad_e,grad_f,grad_g,grad_h'],
            'font_key'          => ['nullable', 'string', 'in:baloo,dancing,pacifico,satisfy,lobster'],
            'youtube_url'       => ['nullable', 'url', 'max:255'],
            'tips'              => ['nullable', 'array'],
            'tips.*'            => ['string', 'max:300'],
        ]);

        $ingredients = $request->input('ingredients', []);
        $totalCost   = collect($ingredients)->sum('price');

        // Build image list: keep existing + add newly uploaded
        $imagePaths = $request->input('existing_images', []);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path         = $file->store("recipes/{$user->id}", 'public');
                $imagePaths[] = url(Storage::url($path));
            }
        }
        $imagePaths = array_values(array_slice($imagePaths, 0, 3));

        $coverFields = [];
        if ($request->has('existing_images') || $request->hasFile('images')) {
            $coverFields['image_url']   = count($imagePaths) > 0 ? $imagePaths[0] : null;
            $coverFields['image_urls']  = count($imagePaths) > 0 ? $imagePaths : null;
        }
        if ($request->filled('collage_style')) {
            $coverFields['collage_style'] = $request->input('collage_style');
        }
        if ($request->filled('gradient_key')) {
            $coverFields['gradient_key'] = $request->input('gradient_key');
        }
        if ($request->filled('font_key')) {
            $coverFields['font_key'] = $request->input('font_key');
        }
        if ($request->has('youtube_url')) {
            $coverFields['youtube_url'] = $request->input('youtube_url') ?: null;
        }

        $recipe->update(array_merge([
            'title'             => $request->title,
            'description'       => $request->description,
            'budget_tag'        => $request->budget_tag,
            'estimated_cost'    => $totalCost,
            'servings'          => $request->servings,
            'prep_time_minutes' => $request->prep_time_minutes,
            'cook_time_minutes' => $request->cook_time_minutes,
            'difficulty'        => $request->difficulty,
            'tags'              => array_values($request->input('tags', [])),
            'steps'             => array_values($request->input('steps', [])),
            'tips'              => array_values($request->input('tips', [])),
        ], $coverFields));

        RecipeIngredient::where('recipe_id', $id)->delete();
        foreach ($ingredients as $i => $ing) {
            RecipeIngredient::create([
                'recipe_id'       => $recipe->id,
                'name'            => $ing['name'],
                'quantity'        => $ing['qty'],
                'unit'            => '',
                'estimated_price' => $ing['price'],
                'sort_order'      => $i,
            ]);
        }

        foreach (($imagePaths ?: []) as $img) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($img, 'recipe.images', $recipe->id);
        }

        return response()->json(['recipe' => $recipe->fresh()->load(['ingredients', 'user:id,name,username'])]);
    }

    /** DELETE /recipes/{id} — a user deleting one of their own recipes. */
    public function destroy(Request $request, int $id)
    {
        $recipe = Recipe::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $this->deleteRecipeAndFiles($recipe);

        return response()->json(['message' => 'Recipe deleted.']);
    }

    /** DELETE /recipes — deletes every recipe the current user owns. */
    public function destroyAll(Request $request)
    {
        $recipes = Recipe::where('user_id', $request->user()->id)->get();

        \Illuminate\Support\Facades\DB::transaction(function () use ($recipes) {
            foreach ($recipes as $recipe) {
                $this->deleteRecipeAndFiles($recipe);
            }
        });

        return response()->json(['message' => "Deleted {$recipes->count()} recipes.", 'deleted' => $recipes->count()]);
    }

    private function deleteRecipeAndFiles(Recipe $recipe): void
    {
        // meal_plan_items.recipe_id has no DB-level FK/cascade, so sever it
        // manually — the meal plan item itself (dish name, its own copy of
        // the ingredients) survives, it just stops linking to this recipe.
        \App\Models\MealPlanItem::where('recipe_id', $recipe->id)->update(['recipe_id' => null]);

        foreach (array_filter([$recipe->image_url, ...($recipe->image_urls ?? [])]) as $url) {
            if (is_string($url) && str_starts_with($url, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $url));
            }
        }

        // Comments, ratings, reactions, ingredients, and the nullable post
        // link all cascade/null at the database level already.
        $recipe->delete();
    }

    public function share(Request $request, int $id)
    {
        $user   = $request->user();
        $recipe = Recipe::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $recipe->update(['is_published' => ! $recipe->is_published]);
        return response()->json(['is_published' => (bool) $recipe->fresh()->is_published]);
    }

    public function book(Request $request)
    {
        $userId = $request->user()->id;

        $saved = RecipeBook::where('user_id', $userId)
            ->with(['recipe' => fn($q) => $q->select([
                'id', 'title', 'description', 'budget_tag', 'estimated_cost',
                'servings', 'prep_time_minutes', 'cook_time_minutes', 'difficulty',
                'tags', 'collage_style', 'gradient_key', 'font_key', 'image_urls',
                'save_count', 'is_published', 'user_id', 'source',
            ])->with('user:id,name,username')])
            ->latest()
            ->paginate(50);

        // Flatten is_mine onto each recipe, same shape as index()/show(), so the
        // mobile client can gate the "by {author}" row consistently everywhere.
        $saved->getCollection()->transform(function ($row) use ($userId) {
            if ($row->recipe) {
                $row->recipe->is_mine = $row->recipe->user_id === $userId;
            }
            return $row;
        });

        return response()->json($saved);
    }
}
