<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentView;
use App\Models\Post;
use App\Models\PostDislike;
use App\Models\PostReaction;
use App\Models\PostSave;
use App\Models\Recipe;
use App\Services\NotificationService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function feed(Request $request)
    {
        $user          = $request->user();
        $typeFilter    = $request->input('type');
        $followingOnly = $request->boolean('following');

        $connectedIds = DB::table('connections')
            ->where('requester_id', $user->id)
            ->where('status', 'connected')
            ->pluck('recipient_id');

        $posts = Post::with([
            'user:id,name,username,avatar',
            'recipe:id,title,image_url,image_urls,collage_style,gradient_key,font_key,budget_tag,estimated_cost',
        ])
            ->withCount('contentViews as views_count')
            ->where(function ($q) use ($user, $connectedIds, $followingOnly) {
                if ($followingOnly) {
                    if ($connectedIds->isEmpty()) {
                        $q->whereRaw('1 = 0');
                    } else {
                        $q->whereIn('user_id', $connectedIds);
                    }
                } else {
                    if ($user->municipality) {
                        $q->where('municipality', $user->municipality);
                    } else {
                        $q->whereNotNull('id');
                    }
                    $q->orWhere('user_id', $user->id);
                    if ($connectedIds->isNotEmpty()) {
                        $q->orWhereIn('user_id', $connectedIds);
                    }
                }
            })
            ->when($typeFilter, fn ($q) => $q->where('post_type', $typeFilter))
            ->latest()
            ->paginate(20);

        // Attach has_reacted + has_saved per post for the current user
        $postIds = $posts->pluck('id');
        $reacted  = PostReaction::where('user_id', $user->id)->whereIn('post_id', $postIds)->pluck('post_id')->flip();
        $disliked = PostDislike::where('user_id', $user->id)->whereIn('post_id', $postIds)->pluck('post_id')->flip();
        $saved    = PostSave::where('user_id', $user->id)->whereIn('post_id', $postIds)->pluck('post_id')->flip();

        $posts->getCollection()->transform(function ($post) use ($reacted, $disliked, $saved) {
            $post->has_reacted  = $reacted->has($post->id);
            $post->has_disliked = $disliked->has($post->id);
            $post->has_saved    = $saved->has($post->id);
            return $post;
        });

        return response()->json($posts);
    }

    // GET /community/post/{id}
    public function show(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $post = Post::with([
            'user:id,name,username,avatar',
            'recipe:id,title,image_url,image_urls,collage_style,gradient_key,font_key,budget_tag,estimated_cost',
        ])->findOrFail($id);

        ContentView::log($post, $user, $post->user_id);
        $post->views_count = $post->contentViews()->count();

        $post->has_reacted  = PostReaction::where('user_id', $user->id)->where('post_id', $id)->exists();
        $post->has_disliked = PostDislike::where('user_id', $user->id)->where('post_id', $id)->exists();
        $post->has_saved    = PostSave::where('user_id', $user->id)->where('post_id', $id)->exists();

        return response()->json(['post' => $post]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'body'          => ['required', 'string', 'max:2000'],
            'post_type'     => ['required', 'in:price_tip,budget_win,general,recipe_share'],
            'recipe_id'     => ['nullable', 'integer', 'exists:recipes,id'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'serving_size'  => ['nullable', 'integer', 'min:1'],
            'images'        => ['nullable', 'array', 'max:3'],
            'images.*'      => ['image', 'max:8192'],
        ]);

        $user = $request->user();

        // Store uploaded images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path         = $file->store("posts/{$user->id}", 'public');
                $imagePaths[] = url(\Illuminate\Support\Facades\Storage::url($path));
            }
        }

        $recipeId = $request->input('recipe_id');

        $post = Post::create([
            'post_type'     => $request->input('post_type'),
            'body'          => $request->input('body'),
            'budget_amount' => $request->input('budget_amount'),
            'serving_size'  => $request->input('serving_size'),
            'recipe_id'     => $recipeId ?: null,
            'user_id'       => $user->id,
            'barangay'      => $user->barangay,
            'municipality'  => $user->municipality,
            'images'        => $imagePaths ?: null,
        ]);

        // Increment share_count on the recipe
        if ($recipeId) {
            Recipe::where('id', $recipeId)->increment('share_count');
        }

        app(XpService::class)->award($user, 30, 'create_post', $post);

        foreach ($imagePaths as $img) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($img, 'post.images', $post->id);
        }

        return response()->json([
            'post' => $post->load([
                'user:id,name,username,avatar',
                'recipe:id,title,image_url,image_urls,collage_style,gradient_key,font_key,budget_tag,estimated_cost',
            ]),
        ], 201);
    }

    public function react(Request $request, int $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $existing = PostReaction::where('user_id', $user->id)
            ->where('post_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('puso_count');
            return response()->json(['reacted' => false, 'puso_count' => max(0, $post->puso_count - 1)]);
        }

        PostReaction::create(['user_id' => $user->id, 'post_id' => $id, 'reaction' => 'puso']);
        $post->increment('puso_count');

        // Notify post owner (skip self-reactions)
        if ($post->user_id !== $user->id) {
            $owner = $post->user ?? \App\Models\User::find($post->user_id);
            if ($owner) {
                app(NotificationService::class)->send(
                    $owner,
                    'reaction',
                    '❤️ Someone liked your post!',
                    "{$user->name} liked your post.",
                    ['post_id' => $id],
                    '/(tabs)/komunidad',
                );
            }
        }

        return response()->json(['reacted' => true, 'puso_count' => $post->puso_count + 1]);
    }

    public function save(Request $request, int $id)
    {
        $user = $request->user();
        Post::findOrFail($id);

        $existing = PostSave::where('user_id', $user->id)->where('post_id', $id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['saved' => false]);
        }

        PostSave::create(['user_id' => $user->id, 'post_id' => $id]);
        return response()->json(['saved' => true]);
    }

    public function dislike(Request $request, int $id)
    {
        $user = $request->user();
        Post::findOrFail($id);

        $existing = PostDislike::where('user_id', $user->id)->where('post_id', $id)->first();

        if ($existing) {
            $existing->delete();
            \App\Models\Post::where('id', $id)->decrement('dislike_count');
            return response()->json(['disliked' => false]);
        }

        PostDislike::create(['user_id' => $user->id, 'post_id' => $id]);
        \App\Models\Post::where('id', $id)->increment('dislike_count');
        return response()->json(['disliked' => true]);
    }

    public function update(Request $request, int $id)
    {
        $post = Post::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($post->created_at->diffInHours(now()) > 72) {
            return response()->json(['message' => 'Hindi na maaaring i-edit ang post na higit sa 3 araw ang edad.'], 403);
        }

        $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);
        $post->update(['body' => $request->body]);

        return response()->json(['post' => $post->load('user:id,name,username,avatar')]);
    }

    public function destroy(Request $request, int $id)
    {
        $post = Post::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        // Decrement share_count when a recipe_share post is removed
        if ($post->post_type === 'recipe_share' && $post->recipe_id) {
            Recipe::where('id', $post->recipe_id)->decrement('share_count');
        }

        $post->delete();
        return response()->json(['message' => 'Na-delete na ang post.']);
    }
}
