<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private UserModerationService $moderation)
    {
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->string('plan'));
        }

        if ($request->filled('banned')) {
            $request->boolean('banned')
                ? $query->whereNotNull('banned_at')
                : $query->whereNull('banned_at');
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        return response()->json(['user' => User::findOrFail($id)]);
    }

    // Filament's UserResource allows create — this is also how admin accounts get
    // bootstrapped/promoted, since there's no separate "invite an admin" flow.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:30', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,admin'],
            'plan' => ['sometimes', 'in:libre,premium'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'plan' => $validated['plan'] ?? 'libre',
            'xp' => 0,
            'level' => 1,
            'onboarding_completed' => true,
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:30'],
            'email' => ['sometimes', 'email', 'max:255'],
            'bio' => ['nullable', 'string', 'max:160'],
            'household_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'role' => ['sometimes', 'in:user,admin'],
            'plan' => ['sometimes', 'in:libre,premium'],
            'premium_expires_at' => ['nullable', 'date'],
            'xp' => ['sometimes', 'integer', 'min:0'],
            'level' => ['sometimes', 'integer', 'min:1'],
            'streak_days' => ['sometimes', 'integer', 'min:0'],
        ]);

        $user->update($validated);

        return response()->json(['user' => $user->fresh()]);
    }

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function ban(Request $request, int $id)
    {
        $validated = $request->validate([
            'ban_reason' => ['required', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($id);
        $this->moderation->ban($user, $validated['ban_reason'], $request->user());

        return response()->json(['user' => $user->fresh()]);
    }

    public function unban(int $id)
    {
        $user = User::findOrFail($id);
        $this->moderation->unban($user);

        return response()->json(['user' => $user->fresh()]);
    }

    /** GET /admin/users/{id}/sessions — this user's devices/logins. No "current" concept -- the admin isn't logged in as them. */
    public function sessions(int $id)
    {
        $user = User::findOrFail($id);

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'device_name' => $t->device_name,
                'platform' => $t->platform,
                'app_version' => $t->app_version,
                'ip_address' => $t->ip_address,
                'last_used_at' => $t->last_used_at,
                'created_at' => $t->created_at,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    /** DELETE /admin/users/{id}/sessions/{tokenId} — force sign-out of one specific device. */
    public function revokeSession(int $id, int $tokenId)
    {
        $user = User::findOrFail($id);
        $user->tokens()->where('id', $tokenId)->firstOrFail()->delete();

        return response()->json(['message' => 'Device signed out.']);
    }

    // Consumer Premium is a plain User attribute (plan/premium_expires_at/
    // premium_source), not a row in the seller Subscription table — this is
    // the "who currently has Premium" view the Monetization section was
    // missing. index() above already supports ?plan=premium filtering for
    // the general Users list; this is the same data shaped for monitoring
    // expiries instead of general account management.
    public function premiumSubscribers(Request $request)
    {
        $query = User::where('plan', 'premium');

        if ($request->filled('source')) {
            $query->where('premium_source', $request->string('source'));
        }

        if ($request->boolean('expiring_soon')) {
            $query->whereNotNull('premium_expires_at')
                ->whereBetween('premium_expires_at', [now(), now()->addDays(7)]);
        }

        return response()->json(
            $query->orderBy('premium_expires_at')
                ->select(['id', 'name', 'username', 'email', 'premium_source', 'premium_expires_at', 'created_at'])
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function premiumSubscribersSummary()
    {
        $base = User::where('plan', 'premium');

        return response()->json([
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('premium_source', 'paid')->count(),
            'trial' => (clone $base)->where('premium_source', 'trial')->count(),
            'expiring_soon' => (clone $base)->whereNotNull('premium_expires_at')
                ->whereBetween('premium_expires_at', [now(), now()->addDays(7)])
                ->count(),
        ]);
    }
}
