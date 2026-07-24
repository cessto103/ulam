<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\AppSetting;
use App\Models\BoostOption;
use App\Models\Recipe;
use App\Models\Tindahan;
use App\Models\User;
use App\Models\UserRewardTier;
use App\Services\BillingService;
use App\Services\BoostService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoostController extends Controller
{
    public function __construct(private BillingService $billing)
    {
    }

    private const TARGET_MODELS = [
        'recipe' => Recipe::class,
        'tindahan' => Tindahan::class,
    ];

    /** Which reward_type a free credit must have to boost each target. */
    private const CREDIT_REWARD_TYPES = [
        'recipe' => 'booster_credit',
        'tindahan' => 'store_boost_credit',
    ];

    /** GET /boosts?target=recipe&boostable_id=5 — the caller's boost history, optionally scoped to one item. */
    public function index(Request $request)
    {
        $query = AdBoost::where('user_id', $request->user()->id)->orderByDesc('created_at');

        if ($request->filled('target') && $request->filled('boostable_id')) {
            $modelClass = self::TARGET_MODELS[$request->string('target')->value()] ?? null;
            if ($modelClass) {
                $query->where('boostable_type', $modelClass)->where('boostable_id', $request->integer('boostable_id'));
            }
        }

        return response()->json(['boosts' => $query->get()]);
    }

    /** POST /boosts — spend a free boost credit earned from a Reward Tier. Real-money boosts go through checkout() instead. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target' => ['required', 'string', Rule::in(array_keys(self::TARGET_MODELS))],
            'boostable_id' => ['required', 'integer'],
            'user_reward_tier_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $modelClass = self::TARGET_MODELS[$validated['target']];

        $boostable = $modelClass::findOrFail($validated['boostable_id']);
        if ($boostable->user_id !== $user->id) {
            return response()->json(['message' => 'You can only boost your own content.'], 403);
        }

        if ($this->alreadyBoosted($modelClass, $boostable->id)) {
            return response()->json(['message' => 'This is already boosted or has a payment awaiting confirmation.'], 422);
        }

        return $this->storeFromCredit($validated, $modelClass, $boostable, $user);
    }

    /** POST /boosts/checkout — starts a PayMongo checkout for a paid boost duration; the boost activates automatically once the webhook confirms payment. */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'target' => ['required', 'string', Rule::in(array_keys(self::TARGET_MODELS))],
            'boostable_id' => ['required', 'integer'],
            'duration_days' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $modelClass = self::TARGET_MODELS[$validated['target']];

        $boostable = $modelClass::findOrFail($validated['boostable_id']);
        if ($boostable->user_id !== $user->id) {
            return response()->json(['message' => 'You can only boost your own content.'], 403);
        }

        if ($this->alreadyBoosted($modelClass, $boostable->id)) {
            return response()->json(['message' => 'This is already boosted or has a payment awaiting confirmation.'], 422);
        }

        $settings = AppSetting::allCached();
        if (($settings['payments_enabled'] ?? '1') !== '1') {
            return response()->json(['message' => 'Payments are temporarily unavailable. Please try again later.'], 503);
        }

        $option = BoostOption::where('target', $validated['target'])
            ->where('duration_days', $validated['duration_days'])
            ->where('is_active', true)
            ->first();

        if (! $option) {
            return response()->json(['message' => 'That boost option is not available.'], 422);
        }

        $session = $this->billing->checkoutBoost($user, $option, $boostable);

        return response()->json([
            'session_id' => $session->public_id, 'status' => $session->status,
            'checkout_url' => $session->checkout_url, 'expires_at' => $session->expires_at,
        ], 201);
    }

    private function alreadyBoosted(string $modelClass, int $boostableId): bool
    {
        return AdBoost::where('boostable_type', $modelClass)
            ->where('boostable_id', $boostableId)
            ->whereIn('status', ['pending', 'active'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function storeFromCredit(array $validated, string $modelClass, Model $boostable, User $user)
    {
        $expectedType = self::CREDIT_REWARD_TYPES[$validated['target']];

        try {
            $boost = app(BoostService::class)->activateFromCredit(
                $boostable, $modelClass, (int) $validated['user_reward_tier_id'], $expectedType, $user,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Boost activated! 🚀',
            'boost' => $boost,
        ], 201);
    }

    /** DELETE /boosts/{id} — withdraw a pending submission. */
    public function destroy(Request $request, int $id)
    {
        $boost = AdBoost::where('user_id', $request->user()->id)->findOrFail($id);

        if ($boost->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be withdrawn.'], 422);
        }

        $boost->delete();

        return response()->json(['message' => 'Submission withdrawn.']);
    }
}
