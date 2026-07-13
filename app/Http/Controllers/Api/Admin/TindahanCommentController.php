<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tindahan;
use App\Models\TindahanComment;
use Illuminate\Http\Request;

// View + delete only, same shape as Admin\PostCommentController — comment
// moderation is a delete-only surface, no create/edit.
class TindahanCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = TindahanComment::with(['user:id,name,username,avatar', 'tindahan:id,name']);

        if ($request->filled('search')) {
            $query->where('body', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('tindahan_id')) {
            $query->where('tindahan_id', $request->integer('tindahan_id'));
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
        $comment = TindahanComment::with(['user:id,name,username,avatar', 'tindahan:id,name'])->findOrFail($id);

        return response()->json(['comment' => $comment]);
    }

    public function destroy(int $id)
    {
        $comment = TindahanComment::findOrFail($id);
        Tindahan::where('id', $comment->tindahan_id)->decrement('comments_count');
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
