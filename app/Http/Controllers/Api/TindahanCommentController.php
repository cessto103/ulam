<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tindahan;
use App\Models\TindahanComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TindahanCommentController extends Controller
{
    // GET /tindahan/{id}/comments
    public function index(int $tindahanId): JsonResponse
    {
        Tindahan::findOrFail($tindahanId);

        $comments = TindahanComment::where('tindahan_id', $tindahanId)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,avatar',
                'replies.user:id,name,username,avatar',
            ])
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    // POST /tindahan/{id}/comments
    public function store(Request $request, int $tindahanId): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:tindahan_comments,id'],
        ]);

        $tindahan = Tindahan::findOrFail($tindahanId);
        $user = $request->user();

        $comment = TindahanComment::create([
            'tindahan_id' => $tindahanId,
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'body' => $request->body,
        ]);

        $tindahan->increment('comments_count');

        if ($tindahan->user_id !== $user->id) {
            $owner = $tindahan->user ?? User::find($tindahan->user_id);
            if ($owner) {
                app(NotificationService::class)->send(
                    $owner,
                    'comment',
                    '💬 New comment on your store!',
                    "{$user->name}: {$request->input('body')}",
                    ['tindahan_id' => $tindahanId],
                    "/stall/{$tindahanId}"
                );
            }
        }

        return response()->json([
            'comment' => $comment->load('user:id,name,username,avatar'),
        ], 201);
    }

    // DELETE /tindahan/comments/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = TindahanComment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        Tindahan::where('id', $comment->tindahan_id)->decrement('comments_count');
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
