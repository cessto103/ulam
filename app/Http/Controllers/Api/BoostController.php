<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\AppSetting;
use App\Models\BoostOption;
use App\Models\Recipe;
use App\Models\Tindahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoostController extends Controller
{
    private const TARGET_MODELS = [
        'recipe' => Recipe::class,
        'tindahan' => Tindahan::class,
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

    /** POST /boosts — submit a manual GCash payment to boost a recipe or store. */
    public function store(Request $request)
    {
        $settings = AppSetting::allCached();
        if (($settings['payments_enabled'] ?? '1') !== '1') {
            return response()->json(['message' => 'Payments are temporarily unavailable. Please try again later.'], 503);
        }

        $validated = $request->validate([
            'target' => ['required', 'string', Rule::in(array_keys(self::TARGET_MODELS))],
            'boostable_id' => ['required', 'integer'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'payment_reference' => ['required', 'regex:/^[0-9]{8,20}$/', 'unique:ad_boosts,payment_reference'],
        ], [
            'payment_reference.regex' => 'The reference number should be the digits from your GCash receipt.',
            'payment_reference.unique' => 'This reference number has already been used.',
        ]);

        $user = $request->user();
        $modelClass = self::TARGET_MODELS[$validated['target']];

        $boostable = $modelClass::findOrFail($validated['boostable_id']);
        if ($boostable->user_id !== $user->id) {
            return response()->json(['message' => 'You can only boost your own content.'], 403);
        }

        $option = BoostOption::where('target', $validated['target'])
            ->where('duration_days', $validated['duration_days'])
            ->where('is_active', true)
            ->first();

        if (! $option) {
            return response()->json(['message' => 'That boost option is not available.'], 422);
        }

        $alreadyBoosted = AdBoost::where('boostable_type', $modelClass)
            ->where('boostable_id', $boostable->id)
            ->whereIn('status', ['pending', 'active'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($alreadyBoosted) {
            return response()->json(['message' => 'This is already boosted or has a payment awaiting review.'], 422);
        }

        $boost = AdBoost::create([
            'user_id' => $user->id,
            'boostable_type' => $modelClass,
            'boostable_id' => $boostable->id,
            'duration_days' => $option->duration_days,
            'amount_paid' => $option->price,
            'payment_method' => 'gcash_manual',
            'payment_reference' => $validated['payment_reference'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Salamat! Your payment is being verified — usually within 24 hours.',
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
