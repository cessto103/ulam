<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\PostSave;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    // GET /users/{id}
    public function profile(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me   = $request->user();
        $user = User::findOrFail($id);

        $followersCount = Connection::where('recipient_id', $id)->where('status', 'connected')->count();
        $followingCount = Connection::where('requester_id', $id)->where('status', 'connected')->count();
        $isFollowing    = Connection::where('requester_id', $me->id)
                            ->where('recipient_id', $id)
                            ->where('status', 'connected')
                            ->exists();

        $posts  = Post::with(['user:id,name,username,avatar'])
                    ->where('user_id', $id)
                    ->latest()
                    ->paginate(10);

        $postIds = $posts->pluck('id');
        $reacted = PostReaction::where('user_id', $me->id)->whereIn('post_id', $postIds)->pluck('post_id')->flip();
        $saved   = PostSave::where('user_id', $me->id)->whereIn('post_id', $postIds)->pluck('post_id')->flip();

        $posts->getCollection()->transform(function ($post) use ($reacted, $saved) {
            $post->has_reacted = $reacted->has($post->id);
            $post->has_saved   = $saved->has($post->id);
            return $post;
        });

        return response()->json([
            'user' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'username'        => $user->username,
                'bio'             => $user->bio,
                'avatar'          => $user->avatar,
                'level'           => $user->level,
                'xp'              => $user->xp,
                'plan'            => $user->plan,
                'municipality'    => $user->municipality,
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'is_following'    => $isFollowing,
                'is_me'           => $me->id === $id,
            ],
            'posts' => $posts,
        ]);
    }

    // POST /users/{id}/follow
    public function follow(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        if ($me->id === $id) {
            return response()->json(['error' => 'Hindi mo masusundan ang iyong sarili.'], 422);
        }

        $conn = Connection::firstOrCreate(
            ['requester_id' => $me->id, 'recipient_id' => $id],
            ['status' => 'connected']
        );

        if ($conn->wasRecentlyCreated) {
            $followed = User::find($id);
            if ($followed) {
                $handle = $me->username ? "@{$me->username}" : $me->name;
                app(NotificationService::class)->send(
                    $followed,
                    'follow',
                    '👤 May bagong sumusunod sa iyo!',
                    "{$handle} ay nagsimulang sumunod sa iyo.",
                    ['follower_id' => $me->id],
                    "/user/{$me->id}"
                );
            }
        }

        $count = Connection::where('recipient_id', $id)->where('status', 'connected')->count();
        return response()->json(['is_following' => true, 'followers_count' => $count]);
    }

    // DELETE /users/{id}/follow
    public function unfollow(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        Connection::where('requester_id', $me->id)->where('recipient_id', $id)->delete();

        $count = Connection::where('recipient_id', $id)->where('status', 'connected')->count();
        return response()->json(['is_following' => false, 'followers_count' => $count]);
    }

    // GET /connections/following
    public function following(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $rows = Connection::where('requester_id', $me->id)
            ->where('status', 'connected')
            ->with('recipient:id,name,username,avatar,level')
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }

    // GET /connections/followers
    public function followers(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $rows = Connection::where('recipient_id', $me->id)
            ->where('status', 'connected')
            ->with('requester:id,name,username,avatar,level')
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }
}
