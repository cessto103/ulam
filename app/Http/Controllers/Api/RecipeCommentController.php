<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeCommentController extends Controller
{
    // GET /recipes/{id}/comments
    public function index(int $recipeId): JsonResponse
    {
        Recipe::findOrFail($recipeId); // 404 if recipe gone

        $comments = RecipeComment::where('recipe_id', $recipeId)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,avatar',
                'replies.user:id,name,username,avatar',
            ])
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    // POST /recipes/{id}/comments
    public function store(Request $request, int $recipeId): JsonResponse
    {
        $request->validate([
            'body'      => ['required', 'string', 'min:1', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:recipe_comments,id'],
        ]);

        $recipe = Recipe::findOrFail($recipeId);
        $user   = $request->user();

        $comment = RecipeComment::create([
            'recipe_id' => $recipeId,
            'user_id'   => $user->id,
            'parent_id' => $request->parent_id,
            'body'      => $request->body,
        ]);

        if ($recipe->user_id && $recipe->user_id !== $user->id) {
            $owner = $recipe->user ?? User::find($recipe->user_id);
            if ($owner) {
                app(NotificationService::class)->send(
                    $owner,
                    'comment',
                    '💬 New comment on your recipe!',
                    "{$user->name}: {$request->input('body')}",
                    ['recipe_id' => $recipeId],
                    "/recipe/{$recipeId}"
                );
            }
        }

        return response()->json([
            'comment' => $comment->load('user:id,name,username,avatar'),
        ], 201);
    }

    // PATCH /recipe-comments/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $comment = RecipeComment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($comment->created_at->diffInHours(now()) > 72) {
            return response()->json(['message' => 'Hindi na maaaring i-edit ang komentong higit sa 3 araw ang edad.'], 403);
        }

        $request->validate(['body' => ['required', 'string', 'min:1', 'max:1000']]);
        $comment->update(['body' => $request->body]);

        return response()->json(['comment' => $comment->load('user:id,name,username,avatar')]);
    }

    // DELETE /recipe-comments/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = RecipeComment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Na-delete na ang komento.']);
    }
}
