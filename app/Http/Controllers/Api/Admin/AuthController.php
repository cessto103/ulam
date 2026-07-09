<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $request->login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mali ang email/username o password.'], 401);
        }

        if (! $user->isAdmin() || $user->isBanned()) {
            return response()->json(['message' => 'Not authorized as admin.'], 403);
        }

        $token = $user->createToken('admin-panel')->plainTextToken;

        return response()->json(['user' => $this->formatAdminUser($user), 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->formatAdminUser($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'username' => ['sometimes', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username,' . $user->id],
        ]);

        $user->update($validated);

        return response()->json(['user' => $this->formatAdminUser($user->fresh())]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['new_password'])]);

        // Log out every other device/session; the token making this request stays valid.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password changed.']);
    }

    private function formatAdminUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'municipality' => $user->municipality,
        ];
    }
}
