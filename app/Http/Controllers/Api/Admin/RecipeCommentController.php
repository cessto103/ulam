<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecipeComment;
use Illuminate\Http\Request;

// View + delete only — matches the Post/Tindahan comment moderation pattern
// (comment moderation is a delete-only surface, no create/edit).
class RecipeCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = RecipeComment::with(['user:id,name,username,avatar', 'recipe:id,title']);

        if ($request->filled('search')) {
            $query->where('body', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('recipe_id')) {
            $query->where('recipe_id', $request->integer('recipe_id'));
        }

        if ($request->filled('is_reply')) {
            $request->boolean('is_reply')
                ? $query->whereNotNull('parent_id')
                : $query->whereNull('parent_id');
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $comment = RecipeComment::with(['user:id,name,username,avatar', 'recipe:id,title'])->findOrFail($id);

        return response()->json(['comment' => $comment]);
    }

    public function destroy(int $id)
    {
        RecipeComment::findOrFail($id)->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
