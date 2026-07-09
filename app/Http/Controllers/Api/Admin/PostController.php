<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('user:id,name,username,avatar')->withCount('comments');

        if ($request->filled('search')) {
            $query->where('body', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('post_type')) {
            $query->where('post_type', $request->string('post_type'));
        }

        if ($request->filled('is_sponsored')) {
            $query->where('is_sponsored', $request->boolean('is_sponsored'));
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', $request->string('municipality'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $post = Post::with('user:id,name,username,avatar')->withCount('comments')->findOrFail($id);

        return response()->json(['post' => $post]);
    }

    // Mirrors Filament's PostResource form: only is_sponsored is editable —
    // body/post_type/location fields stay read-only, moderation is delete-only for content.
    public function update(Request $request, int $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'is_sponsored' => ['required', 'boolean'],
        ]);

        $post->update($validated);

        return response()->json(['post' => $post->fresh()]);
    }

    public function destroy(int $id)
    {
        Post::findOrFail($id)->delete();

        return response()->json(['message' => 'Post deleted.']);
    }
}
