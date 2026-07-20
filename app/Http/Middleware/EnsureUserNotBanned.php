<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserNotBanned
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->banned_at) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been suspended.' . ($user->ban_reason ? " Reason: {$user->ban_reason}" : ''),
                'code' => 'banned',
            ], 403);
        }

        return $next($request);
    }
}
