<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\ConnectionLabel;
use App\Models\Follow;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\PostSave;
use App\Models\User;
use App\Models\UserRewardTier;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    // GET /users/{id}
    public function profile(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me   = $request->user();
        $user = User::findOrFail($id);

        $followersCount = Follow::where('followed_id', $id)->count();
        $followingCount = Follow::where('follower_id', $id)->count();
        $isFollowing    = Follow::where('follower_id', $me->id)
                            ->where('followed_id', $id)
                            ->exists();

        // Mutual-connection state between me and this profile (additive keys —
        // old clients ignore them).
        $connection       = $this->connectionBetween($me->id, $id);
        $connectionStatus = 'none';
        $myLabelId        = null;
        if ($connection) {
            if ($connection->status === 'connected') {
                $connectionStatus = 'connected';
                $myLabelId = $connection->requester_id === $me->id
                    ? $connection->requester_label_id
                    : $connection->recipient_label_id;
            } elseif ($connection->status === 'pending') {
                $connectionStatus = $connection->requester_id === $me->id ? 'pending_sent' : 'pending_received';
            } else {
                $connectionStatus = 'blocked';
            }
        }

        $posts  = Post::with([
                        'user:id,name,username,avatar',
                        'recipe:id,title,image_url,image_urls,collage_style,gradient_key,font_key,budget_tag,estimated_cost',
                    ])
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

        // Active stores owned by this user (for the profile's store strip)
        $stores = \App\Models\Tindahan::where('user_id', $id)
            ->publiclyVisible()
            ->get(['id', 'name', 'type', 'barangay', 'municipality', 'photo', 'is_verified'])
            ->map(function ($t) {
                $priceQ = \App\Models\MarketPrice::where('tindahan_id', $t->id)->where('is_available', true);
                $t->item_count   = (clone $priceQ)->count();
                $t->last_updated = (clone $priceQ)->max('updated_at');
                return $t;
            });

        // Public, cosmetic-only slice of Reward Tiers -- deliberately not the
        // authenticated /user/reward-tiers endpoint, which also exposes
        // locked progress and unspent credits that shouldn't leak to viewers.
        $badges = UserRewardTier::where('user_id', $id)
            ->whereNotNull('redeemed_at')
            ->whereHas('rewardTier', fn ($q) => $q->where('reward_type', 'badge'))
            ->with('rewardTier:id,title,title_en,icon')
            ->get()
            ->map(fn ($urt) => [
                'id'       => $urt->reward_tier_id,
                'title'    => $urt->rewardTier->title,
                'title_en' => $urt->rewardTier->title_en,
                'icon'     => $urt->rewardTier->icon,
            ]);

        return response()->json([
            'stores' => $stores,
            'user' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'username'          => $user->username,
                'bio'               => $user->bio,
                'avatar'            => $user->avatar,
                'level'             => $user->level,
                'xp'                => $user->xp,
                'plan'              => $user->plan,
                'municipality'      => $user->municipality,
                'followers_count'   => $followersCount,
                'following_count'   => $followingCount,
                'is_following'      => $isFollowing,
                'is_me'             => $me->id === $id,
                'connection_status' => $connectionStatus,
                'connection_id'     => $connection?->id,
                'my_label_id'       => $myLabelId,
                'badges'            => $badges,
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

        $follow = Follow::firstOrCreate([
            'follower_id' => $me->id,
            'followed_id' => $id,
        ]);

        if ($follow->wasRecentlyCreated) {
            $followed = User::find($id);
            if ($followed) {
                $handle = $me->username ? "@{$me->username}" : $me->name;
                app(NotificationService::class)->send(
                    $followed,
                    'follow',
                    '👤 You have a new follower!',
                    "{$handle} started following you.",
                    ['follower_id' => $me->id],
                    "/user/{$me->id}"
                );
            }
        }

        $count = Follow::where('followed_id', $id)->count();
        return response()->json(['is_following' => true, 'followers_count' => $count]);
    }

    // DELETE /users/{id}/follow
    public function unfollow(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        Follow::where('follower_id', $me->id)->where('followed_id', $id)->delete();

        $count = Follow::where('followed_id', $id)->count();
        return response()->json(['is_following' => false, 'followers_count' => $count]);
    }

    // GET /connections/following
    public function following(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $rows = Follow::where('follower_id', $me->id)
            ->with('recipient:id,name,username,avatar,level')
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }

    // GET /connections/followers
    public function followers(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $rows = Follow::where('followed_id', $me->id)
            ->with('requester:id,name,username,avatar,level')
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }

    // ─── Mutual connections ──────────────────────────────────────────────────

    // GET /connections — accepted connections, each shaped from my perspective.
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();

        $rows = Connection::where('status', 'connected')
            ->where(fn ($q) => $q->where('requester_id', $me->id)->orWhere('recipient_id', $me->id))
            ->with([
                'requester:id,name,username,avatar,level',
                'recipient:id,name,username,avatar,level',
                'requesterLabel:id,name',
                'recipientLabel:id,name',
            ])
            ->latest()
            ->get()
            ->map(function (Connection $c) use ($me) {
                $iAmRequester = $c->requester_id === $me->id;
                return [
                    'id'          => $c->id,
                    'user'        => $iAmRequester ? $c->recipient : $c->requester,
                    'my_label_id' => $iAmRequester ? $c->requester_label_id : $c->recipient_label_id,
                    'my_label'    => $iAmRequester ? $c->requesterLabel?->name : $c->recipientLabel?->name,
                    'created_at'  => $c->created_at,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    // GET /connections/pending
    public function pending(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();

        $incoming = Connection::where('status', 'pending')
            ->where('recipient_id', $me->id)
            ->with('requester:id,name,username,avatar,level')
            ->latest()
            ->get(['id', 'requester_id', 'created_at']);

        $outgoing = Connection::where('status', 'pending')
            ->where('requester_id', $me->id)
            ->with('recipient:id,name,username,avatar,level')
            ->latest()
            ->get(['id', 'recipient_id', 'created_at']);

        return response()->json(['incoming' => $incoming, 'outgoing' => $outgoing]);
    }

    // GET /connection-labels — active labels for the mobile picker.
    public function labels(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'labels' => ConnectionLabel::active()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    // POST /connections/requests {user_id}
    public function request(Request $request): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $targetId = (int) $validated['user_id'];

        if ($targetId === $me->id) {
            return response()->json(['message' => 'Hindi mo ma-connect ang iyong sarili.'], 422);
        }

        $existing = $this->connectionBetween($me->id, $targetId);
        if ($existing) {
            $message = match ($existing->status) {
                'connected' => 'Magkakonekta na kayo.',
                'pending'   => $existing->requester_id === $me->id
                    ? 'May hinihintay ka nang request sa user na ito.'
                    : 'May request na ang user na ito sa iyo. Tingnan ang iyong mga koneksyon.',
                default     => 'Hindi maaaring mag-request sa user na ito.',
            };
            return response()->json(['message' => $message], 422);
        }

        $connection = Connection::create([
            'requester_id' => $me->id,
            'recipient_id' => $targetId,
            'status'       => 'pending',
        ]);

        $target = User::find($targetId);
        if ($target) {
            $handle = $me->username ? "@{$me->username}" : $me->name;
            app(NotificationService::class)->send(
                $target,
                'connection_request',
                '🤝 You have a connection request!',
                "{$handle} wants to connect with you.",
                ['requester_id' => $me->id],
                '/connections'
            );
        }

        return response()->json(['connection' => $connection], 201);
    }

    // POST /connections/requests/{id}/accept
    public function accept(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $connection = Connection::findOrFail($id);

        if ($connection->status !== 'pending' || $connection->recipient_id !== $me->id) {
            return response()->json(['message' => 'Hindi mo ma-accept ang request na ito.'], 403);
        }

        $connection->update(['status' => 'connected']);

        $requester = User::find($connection->requester_id);
        if ($requester) {
            $handle = $me->username ? "@{$me->username}" : $me->name;
            app(NotificationService::class)->send(
                $requester,
                'connection_accepted',
                '🎉 Your connection request was accepted!',
                "{$handle} accepted your request. You're connected now.",
                ['accepter_id' => $me->id],
                "/user/{$me->id}"
            );
        }

        return response()->json(['connection' => $connection->fresh()]);
    }

    // POST /connections/requests/{id}/decline
    public function decline(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $connection = Connection::findOrFail($id);

        if ($connection->status !== 'pending' || $connection->recipient_id !== $me->id) {
            return response()->json(['message' => 'Hindi mo ma-decline ang request na ito.'], 403);
        }

        $connection->delete();

        return response()->json(['message' => 'Request declined.']);
    }

    // DELETE /connections/requests/{id} — requester cancels their own pending request.
    public function cancel(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $connection = Connection::findOrFail($id);

        if ($connection->status !== 'pending' || $connection->requester_id !== $me->id) {
            return response()->json(['message' => 'Hindi mo ma-cancel ang request na ito.'], 403);
        }

        $connection->delete();

        return response()->json(['message' => 'Request cancelled.']);
    }

    // DELETE /connections/{id} — either party removes an accepted connection.
    public function remove(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $connection = Connection::findOrFail($id);

        if ($connection->status !== 'connected' || ! $connection->involves($me->id)) {
            return response()->json(['message' => 'Hindi mo ma-remove ang koneksyong ito.'], 403);
        }

        $otherId = $connection->otherUserId($me->id);
        $connection->delete();

        // Disconnecting revokes any active shopping-list shares between the
        // two users, both directions. (Wired up in the shopping-lists phase —
        // guarded so this endpoint works before that table exists.)
        if (\Illuminate\Support\Facades\Schema::hasTable('shopping_list_shares')) {
            \Illuminate\Support\Facades\DB::table('shopping_list_shares')
                ->whereIn('shopping_list_id', function ($q) use ($me) {
                    $q->select('id')->from('shopping_lists')->where('owner_id', $me->id);
                })
                ->where('user_id', $otherId)
                ->delete();
            \Illuminate\Support\Facades\DB::table('shopping_list_shares')
                ->whereIn('shopping_list_id', function ($q) use ($otherId) {
                    $q->select('id')->from('shopping_lists')->where('owner_id', $otherId);
                })
                ->where('user_id', $me->id)
                ->delete();
        }

        return response()->json(['message' => 'Connection removed.']);
    }

    // PATCH /connections/{id}/label {label_id} — writes only MY side's column.
    public function setLabel(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $me = $request->user();
        $connection = Connection::findOrFail($id);

        if ($connection->status !== 'connected' || ! $connection->involves($me->id)) {
            return response()->json(['message' => 'Hindi mo ma-label ang koneksyong ito.'], 403);
        }

        $validated = $request->validate([
            'label_id' => ['nullable', 'integer', 'exists:connection_labels,id'],
        ]);

        $column = $connection->requester_id === $me->id ? 'requester_label_id' : 'recipient_label_id';
        $connection->update([$column => $validated['label_id'] ?? null]);

        return response()->json(['connection' => $connection->fresh(['requesterLabel', 'recipientLabel'])]);
    }

    private function connectionBetween(int $userA, int $userB): ?Connection
    {
        return Connection::where(function ($q) use ($userA, $userB) {
                $q->where('requester_id', $userA)->where('recipient_id', $userB);
            })
            ->orWhere(function ($q) use ($userA, $userB) {
                $q->where('requester_id', $userB)->where('recipient_id', $userA);
            })
            ->first();
    }
}
