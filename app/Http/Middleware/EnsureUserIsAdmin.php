<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || $user->isBanned()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }

        return $next($request);
    }
}
