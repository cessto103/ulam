<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:30', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'household_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'household_size' => $validated['household_size'] ?? 4,
            'barangay' => $validated['barangay'] ?? null,
            'municipality' => $validated['municipality'] ?? null,
            'province' => $validated['province'] ?? null,
            'plan' => 'libre',
            'xp' => 0,
            'level' => 1,
            'ai_meal_plans_used_this_month' => 0,
            'ai_quota_reset_date' => now()->startOfMonth()->toDateString(),
        ]);

        Mail::to($user->email)->queue(new WelcomeMail($user));

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mali ang email/username o password.'], 401);
        }

        $user->update(['last_active_date' => now()->toDateString()]);
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Naka-logout na.']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'plan' => $user->plan,
            'household_size' => $user->household_size,
            'barangay' => $user->barangay,
            'municipality' => $user->municipality,
            'province' => $user->province,
            'xp' => $user->xp,
            'level' => $user->level,
            'streak_days' => $user->streak_days,
            'ai_plans_remaining' => $user->isPremium() ? null : max(0, 3 - $user->ai_meal_plans_used_this_month),
            'onboarding_completed' => (bool) $user->onboarding_completed,
        ];
    }
}
