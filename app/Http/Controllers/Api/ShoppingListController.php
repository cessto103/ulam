<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use App\Services\BudgetLogService;
use App\Services\NotificationService;
use App\Services\ShoppingListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShoppingListController extends Controller
{
    // GET /shopping-lists
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();

        $dailyToday = ShoppingList::with('items')
            ->where('owner_id', $me->id)
            ->where('type', 'daily')
            ->whereDate('list_date', today())
            ->first();

        $events = ShoppingList::with('items')
            ->where('owner_id', $me->id)
            ->where('type', 'event')
            ->latest()
            ->get();

        $sharedWithMe = $me->sharedShoppingLists()
            ->with(['items', 'owner:id,name,username,avatar'])
            ->latest('shopping_lists.created_at')
            ->get();

        return response()->json([
            'daily_today'    => $dailyToday ? $this->summarize($dailyToday) : null,
            'events'         => $events->map(fn ($l) => $this->summarize($l)),
            'shared_with_me' => $sharedWithMe->map(fn ($l) => $this->summarize($l, includeOwner: true)),
        ]);
    }

    // POST /shopping-lists/daily {date?}
    public function openDaily(Request $request, ShoppingListService $service): JsonResponse
    {
        $me = $request->user();
        $date = $request->input('date', today()->toDateString());

        $existing = ShoppingList::where('owner_id', $me->id)
            ->where('type', 'daily')
            ->whereDate('list_date', $date)
            ->first();

        if ($existing) {
            return $this->show($request, $existing->id);
        }

        $plan = MealPlan::with('items.ingredients')
            ->where('user_id', $me->id)
            ->whereDate('plan_date', $date)
            ->latest()
            ->first();

        if (! $plan) {
            return response()->json([
                'message' => 'Gumawa muna ng meal plan para sa araw na ito.',
                'no_meal_plan' => true,
            ], 422);
        }

        $list = $service->createDailyFromMealPlan($me, $plan, $date);

        return $this->show($request, $list->id);
    }

    // POST /shopping-lists {title, recipe_id?} — event list.
    public function store(Request $request, ShoppingListService $service): JsonResponse
    {
        $me = $request->user();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'recipe_id' => ['nullable', 'integer', 'exists:recipes,id'],
        ]);

        $recipe = null;
        if (! empty($validated['recipe_id'])) {
            $recipe = Recipe::with('ingredients')->find($validated['recipe_id']);
            if ($recipe && $recipe->is_premium_only && ! $me->isPremium() && $recipe->user_id !== $me->id) {
                return response()->json(['message' => 'Premium recipe ito.'], 403);
            }
        }

        $list = $service->createEventFromRecipe($me, $validated['title'], $recipe);

        return $this->show($request, $list->id);
    }

    // GET /shopping-lists/{id}
    public function show(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::with([
            'items.addedBy:id,name',
            'items.checkedBy:id,name',
            'shares.user:id,name,username,avatar',
            'owner:id,name,username,avatar',
        ])->findOrFail($id);

        if (! $list->isParticipant($me->id)) {
            return response()->json(['message' => 'Wala kang access sa listahang ito.'], 403);
        }

        return response()->json([
            'list' => [
                ...$list->toArray(),
                'my_role'      => $list->isOwner($me->id) ? 'owner' : 'recipient',
                'all_total'    => $list->allTotal(),
                'bought_total' => $list->boughtTotal(),
            ],
        ]);
    }

    // PATCH /shopping-lists/{id} {title}
    public function update(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isOwner($me->id)) {
            return response()->json(['message' => 'Ang may-ari lang ang maaaring mag-edit.'], 403);
        }
        if (! $list->isActive()) {
            return response()->json(['message' => 'Kumpleto na ang listahang ito.'], 409);
        }

        $validated = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $list->update($validated);

        return $this->show($request, $id);
    }

    // DELETE /shopping-lists/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isOwner($me->id)) {
            return response()->json(['message' => 'Ang may-ari lang ang maaaring mag-delete.'], 403);
        }

        $list->delete();

        return response()->json(['message' => 'Nabura ang listahan.']);
    }

    // POST /shopping-lists/{id}/items {name, quantity?, unit?, est_price?}
    public function addItem(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isParticipant($me->id)) {
            return response()->json(['message' => 'Wala kang access sa listahang ito.'], 403);
        }
        if (! $list->isActive()) {
            return response()->json(['message' => 'Kumpleto na ang listahang ito.'], 409);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'string', 'max:40'],
            'unit' => ['nullable', 'string', 'max:30'],
            'est_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $maxSort = (int) $list->items()->max('sort_order');
        $item = $list->items()->create([
            ...$validated,
            'est_price' => $validated['est_price'] ?? 0,
            'added_by' => $me->id,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['item' => $item->load('addedBy:id,name')], 201);
    }

    // PATCH /shopping-lists/{id}/items/{itemId} {is_checked?, quantity?, unit?, actual_price?}
    public function updateItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isParticipant($me->id)) {
            return response()->json(['message' => 'Wala kang access sa listahang ito.'], 403);
        }
        if (! $list->isActive()) {
            return response()->json(['message' => 'Kumpleto na ang listahang ito.'], 409);
        }

        $item = $list->items()->findOrFail($itemId);

        $validated = $request->validate([
            'is_checked' => ['sometimes', 'boolean'],
            'quantity' => ['sometimes', 'nullable', 'string', 'max:40'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:30'],
            'actual_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('is_checked', $validated)) {
            $validated['checked_by'] = $validated['is_checked'] ? $me->id : null;
        }

        $item->update($validated);

        return response()->json(['item' => $item->fresh(['addedBy:id,name', 'checkedBy:id,name'])]);
    }

    // POST /shopping-lists/{id}/items/bulk-check {item_ids, is_checked}
    public function bulkCheck(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isParticipant($me->id)) {
            return response()->json(['message' => 'Wala kang access sa listahang ito.'], 403);
        }
        if (! $list->isActive()) {
            return response()->json(['message' => 'Kumpleto na ang listahang ito.'], 409);
        }

        $validated = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer'],
            'is_checked' => ['required', 'boolean'],
        ]);

        $list->items()->whereIn('id', $validated['item_ids'])->update([
            'is_checked' => $validated['is_checked'],
            'checked_by' => $validated['is_checked'] ? $me->id : null,
        ]);

        return response()->json(['message' => 'OK']);
    }

    // DELETE /shopping-lists/{id}/items/{itemId}
    public function destroyItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isOwner($me->id)) {
            return response()->json(['message' => 'Ang may-ari lang ang maaaring mag-delete ng item.'], 403);
        }
        if (! $list->isActive()) {
            return response()->json(['message' => 'Kumpleto na ang listahang ito.'], 409);
        }

        $list->items()->findOrFail($itemId)->delete();

        return response()->json(['message' => 'Natanggal ang item.']);
    }

    // POST /shopping-lists/{id}/shares {user_ids} — Premium-gated on the OWNER only.
    public function share(Request $request, int $id): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isOwner($me->id)) {
            return response()->json(['message' => 'Ang may-ari lang ang maaaring mag-share.'], 403);
        }
        if (! $me->isPremium()) {
            return response()->json([
                'message' => 'Premium feature ang pag-share ng shopping list.',
                'premium_required' => true,
            ], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // Every recipient must be an accepted connection (either direction).
        // The relationship label is irrelevant — any connection can receive.
        $userIds = collect($validated['user_ids'])->unique()->reject(fn ($uid) => $uid === $me->id);
        $notConnected = $userIds->filter(function ($uid) use ($me) {
            return ! Connection::where('status', 'connected')
                ->where(function ($q) use ($me, $uid) {
                    $q->where(fn ($w) => $w->where('requester_id', $me->id)->where('recipient_id', $uid))
                      ->orWhere(fn ($w) => $w->where('requester_id', $uid)->where('recipient_id', $me->id));
                })
                ->exists();
        });

        if ($notConnected->isNotEmpty()) {
            return response()->json([
                'message' => 'May mga user na hindi mo pa koneksyon.',
                'not_connected_user_ids' => $notConnected->values(),
            ], 422);
        }

        $notifier = app(NotificationService::class);
        $handle = $me->username ? "@{$me->username}" : $me->name;

        foreach ($userIds as $uid) {
            $share = $list->shares()->firstOrCreate(['user_id' => $uid]);
            if ($share->wasRecentlyCreated) {
                $recipient = User::find($uid);
                if ($recipient) {
                    $notifier->send(
                        $recipient,
                        'list_shared',
                        '🛒 May shared shopping list ka!',
                        "{$handle} ay nag-share ng \"{$list->title}\" sa iyo.",
                        ['shopping_list_id' => $list->id],
                        "/shopping-list/{$list->id}"
                    );
                }
            }
        }

        return $this->show($request, $id);
    }

    // DELETE /shopping-lists/{id}/shares/{userId} — owner, or recipient removing self.
    public function unshare(Request $request, int $id, int $userId): JsonResponse
    {
        $me = $request->user();
        $list = ShoppingList::findOrFail($id);

        if (! $list->isOwner($me->id) && $userId !== $me->id) {
            return response()->json(['message' => 'Hindi mo maaaring tanggalin ang share na ito.'], 403);
        }

        $list->shares()->where('user_id', $userId)->delete();

        return response()->json(['message' => 'Share removed.']);
    }

    // POST /shopping-lists/{id}/complete {log_to_budget?, use_full_total?}
    public function complete(Request $request, int $id): JsonResponse
    {
        $me = $request->user();

        $validated = $request->validate([
            'log_to_budget' => ['sometimes', 'boolean'],
            'use_full_total' => ['sometimes', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($me, $id, $validated) {
            $list = ShoppingList::with('items')->lockForUpdate()->findOrFail($id);

            if (! $list->isOwner($me->id)) {
                return ['error' => ['Ang may-ari lang ang maaaring mag-complete.', 403]];
            }
            if (! $list->isActive()) {
                return ['error' => ['Kumpleto na ang listahang ito.', 409]];
            }

            $logToBudget = (bool) ($validated['log_to_budget'] ?? false);
            if ($logToBudget && $list->type !== 'daily') {
                // Event lists are group money, not anyone's personal food
                // budget — completing them never writes a budget log.
                return ['error' => ['Ang event list ay hindi naka-log sa personal na budget.', 422]];
            }

            $spent = ($validated['use_full_total'] ?? false)
                ? $list->allTotal()
                : $list->boughtTotal();

            $logResult = null;
            if ($logToBudget) {
                // Breakdown grouped by each item's source meal type; custom
                // adds (no meal_type) fall under 'iba pa' — same shape the
                // old client-side list sent to /budget/log.
                $useFull = (bool) ($validated['use_full_total'] ?? false);
                $breakdown = $list->items
                    ->filter(fn ($i) => $useFull || $i->is_checked)
                    ->groupBy(fn ($i) => $i->meal_type ?: 'iba pa')
                    ->map(fn ($items, $type) => [
                        'category' => $type,
                        'amount'   => round($items->sum(fn ($i) => (float) ($i->actual_price ?? $i->est_price)), 2),
                    ])
                    ->values()
                    ->all();

                $logResult = app(BudgetLogService::class)->logToday($me, $spent, $breakdown);
                if (! $logResult) {
                    return ['error' => ['I-setup muna ang budget.', 422]];
                }
            }

            $list->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'total_spent'  => $spent,
            ]);

            return ['list' => $list, 'logResult' => $logResult];
        });

        if (isset($result['error'])) {
            [$message, $code] = $result['error'];
            return response()->json(['message' => $message], $code);
        }

        // Notify recipients outside the transaction (network calls).
        $list = $result['list']->fresh(['shares.user']);
        $notifier = app(NotificationService::class);
        $handle = $me->username ? "@{$me->username}" : $me->name;
        foreach ($list->shares as $share) {
            if ($share->user) {
                $notifier->send(
                    $share->user,
                    'list_completed',
                    '✅ Tapos na ang shopping list!',
                    "Minarkahan ni {$handle} na kumpleto na ang \"{$list->title}\".",
                    ['shopping_list_id' => $list->id],
                    "/shopping-list/{$list->id}"
                );
            }
        }

        $reward = $result['logResult']['reward'] ?? null;

        return response()->json([
            'list' => [
                ...$list->load('items')->toArray(),
                'my_role'      => 'owner',
                'all_total'    => $list->allTotal(),
                'bought_total' => $list->boughtTotal(),
            ],
            'xp_earned'  => $reward['xp_awarded'] ?? 0,
            'leveled_up' => $reward['leveled_up'] ?? false,
            'new_level'  => $reward['new_level'] ?? null,
        ]);
    }

    private function summarize(ShoppingList $list, bool $includeOwner = false): array
    {
        $summary = [
            'id'            => $list->id,
            'type'          => $list->type,
            'title'         => $list->title,
            'list_date'     => $list->list_date?->toDateString(),
            'status'        => $list->status,
            'completed_at'  => $list->completed_at,
            'total_spent'   => $list->total_spent,
            'items_count'   => $list->items->count(),
            'checked_count' => $list->items->where('is_checked', true)->count(),
            'all_total'     => $list->allTotal(),
            'bought_total'  => $list->boughtTotal(),
            'created_at'    => $list->created_at,
        ];

        if ($includeOwner) {
            $summary['owner'] = $list->owner;
        }

        return $summary;
    }
}
