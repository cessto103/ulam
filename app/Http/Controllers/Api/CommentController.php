<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // GET /community/post/{id}/comments
    public function index(int $postId): JsonResponse
    {
        Post::findOrFail($postId); // 404 if post gone

        $comments = PostComment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,avatar',
                'replies.user:id,name,username,avatar',
            ])
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    // POST /community/post/{id}/comments
    public function store(Request $request, int $postId): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $post = Post::findOrFail($postId);
        $user = $request->user();

        $comment = PostComment::create([
            'post_id' => $postId,
            'user_id' => $user->id,
            'body'    => $request->body,
        ]);

        $post->increment('comments_count');

        if ($post->user_id !== $user->id) {
            $owner = $post->user ?? User::find($post->user_id);
            if ($owner) {
                app(NotificationService::class)->send(
                    $owner,
                    'comment',
                    '💬 New comment on your post!',
                    "{$user->name}: {$request->input('body')}",
                    ['post_id' => $postId],
                    "/post/{$postId}"
                );
            }
        }

        return response()->json([
            'comment' => $comment->load('user:id,name,username,avatar'),
        ], 201);
    }

    // PATCH /community/comment/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $comment = PostComment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($comment->created_at->diffInHours(now()) > 72) {
            return response()->json(['message' => 'Hindi na maaaring i-edit ang komentong higit sa 3 araw ang edad.'], 403);
        }

        $request->validate(['body' => ['required', 'string', 'min:1', 'max:1000']]);
        $comment->update(['body' => $request->body]);

        return response()->json(['comment' => $comment->load('user:id,name,username,avatar')]);
    }

    // DELETE /community/comment/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = PostComment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        Post::where('id', $comment->post_id)->decrement('comments_count');
        $comment->delete();

        return response()->json(['message' => 'Na-delete na ang komento.']);
    }
}
