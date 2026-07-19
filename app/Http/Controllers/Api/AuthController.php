<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

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

        // Mail is queued but QUEUE_CONNECTION=sync runs it inline, in this
        // same request — an unwrapped send here used to take the whole
        // registration down (500, no token) whenever the mail provider
        // rejected the recipient, even though the account had already been
        // created. Never let mail delivery fail the request.
        try {
            Mail::to($user->email)->queue(new WelcomeMail($user));
        } catch (\Throwable $e) {
            Log::error('Welcome mail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $code = (string) random_int(100000, 999999);
        $user->update([
            'email_verification_otp' => Hash::make($code),
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerificationOtpMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('Email verification mail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 201);
    }

    /** POST /auth/verify-email — confirms the code emailed at registration. */
    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['user' => $this->formatUser($user)]);
        }

        if (!$user->email_verification_otp || !$user->email_verification_otp_expires_at) {
            throw ValidationException::withMessages(['code' => 'No pending verification. Please request a new code.']);
        }

        if ($user->email_verification_otp_expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Please request a new one.']);
        }

        if (!Hash::check($validated['code'], $user->email_verification_otp)) {
            throw ValidationException::withMessages(['code' => 'Incorrect code.']);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ]);

        return response()->json(['user' => $this->formatUser($user)]);
    }

    /** POST /auth/resend-verification — emails a fresh 6-digit code. */
    public function resendEmailVerification(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Your email is already verified.']);
        }

        $code = (string) random_int(100000, 999999);
        $user->update([
            'email_verification_otp' => Hash::make($code),
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerificationOtpMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('Email verification mail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Verification code sent.']);
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

        if ($user->isBanned()) {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
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

    /**
     * POST /auth/forgot-password — emails a 6-digit reset code.
     * Always answers 200 with the same message so the endpoint can't be
     * used to probe which emails have accounts.
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user && !$user->isBanned()) {
            $code = (string) random_int(100000, 999999);

            $user->update([
                'password_reset_otp' => Hash::make($code),
                'password_reset_otp_expires_at' => now()->addMinutes(10),
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetOtpMail($user, $code));
            } catch (\Throwable $e) {
                Log::error('Password reset mail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => 'If that email has an account, a reset code is on its way.',
        ]);
    }

    /** POST /auth/reset-password — verifies the code and sets the new password. */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !$user->password_reset_otp || !$user->password_reset_otp_expires_at) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code. Please request a new one.']);
        }

        if ($user->password_reset_otp_expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Please request a new one.']);
        }

        if (!Hash::check($validated['code'], $user->password_reset_otp)) {
            throw ValidationException::withMessages(['code' => 'Incorrect code.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_reset_otp' => null,
            'password_reset_otp_expires_at' => null,
        ]);

        // Force every existing session to log in again with the new password.
        $user->tokens()->delete();

        return response()->json(['message' => 'Password updated. You can now log in.']);
    }

    /**
     * DELETE /auth/account — permanently deletes the account after password
     * confirmation. Cleanup is done explicitly in application code (the dev
     * DB was created without FK constraints, so nothing cascades): every
     * table with a user_id column is purged except the payment ledger
     * (financial records stay) and markets (shared infrastructure — only
     * unlinked from the user).
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Incorrect password.']);
        }

        if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
            try {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            } catch (\Throwable $e) {
                // best-effort only
            }
        }

        Log::info('Account deleted', ['user_id' => $user->id, 'email' => $user->email]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            $keepRows   = ['payments', 'refunds'];      // financial ledger is retained
            $unlinkOnly = ['markets'];                   // shared infrastructure, not personal data

            $tables = \Illuminate\Support\Facades\DB::select(
                "SELECT TABLE_NAME AS t FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'user_id' AND TABLE_NAME != 'users'"
            );

            foreach ($tables as $table) {
                if (in_array($table->t, $keepRows, true)) {
                    continue;
                }
                if (in_array($table->t, $unlinkOnly, true)) {
                    \Illuminate\Support\Facades\DB::table($table->t)->where('user_id', $user->id)->update(['user_id' => null]);
                    continue;
                }
                \Illuminate\Support\Facades\DB::table($table->t)->where('user_id', $user->id)->delete();
            }

            // Relationship tables that reference users under other column names
            \Illuminate\Support\Facades\DB::table('connections')
                ->where('requester_id', $user->id)->orWhere('recipient_id', $user->id)->delete();
            \Illuminate\Support\Facades\DB::table('follows')
                ->where('follower_id', $user->id)->orWhere('followed_id', $user->id)->delete();
            // shopping_lists uses owner_id, so the generic user_id loop above
            // misses it (items/shares then cascade via their FKs).
            \Illuminate\Support\Facades\DB::table('shopping_lists')
                ->where('owner_id', $user->id)->delete();

            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Your account has been deleted.']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
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
            'ai_plans_remaining' => $user->isPremium() ? null : 0,
            'onboarding_completed' => (bool) $user->onboarding_completed,
        ];
    }
}
