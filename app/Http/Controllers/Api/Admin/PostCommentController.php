<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use Illuminate\Http\Request;

// View + delete only — matches Filament's PostCommentResource exactly (no create/edit;
// comment moderation is a delete-only surface).
class PostCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = PostComment::with(['user:id,name,username,avatar', 'post:id,body']);

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%"))
                    ->orWhereHas('post', fn ($p) => $p->where('body', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', $request->integer('post_id'));
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
        $comment = PostComment::with(['user:id,name,username,avatar', 'post:id,body'])->findOrFail($id);

        return response()->json(['comment' => $comment]);
    }

    public function destroy(int $id)
    {
        PostComment::findOrFail($id)->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
