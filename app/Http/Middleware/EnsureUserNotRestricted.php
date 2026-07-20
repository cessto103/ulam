<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Route-scoped (see the 'not-restricted' alias in bootstrap/app.php), unlike
 * EnsureUserNotBanned which is appended globally -- a strike-2 restriction
 * only blocks specific content-creation routes, not the whole app.
 */
class EnsureUserNotRestricted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isRestricted()) {
            $reason = $user->strikes()->where('level', 2)->latest()->value('reason');

            return response()->json([
                'message' => "You're temporarily restricted from posting until {$user->restricted_until->format('M j, Y')}."
                    . ($reason ? " Reason: {$reason}." : ''),
                'code' => 'restricted',
                'restricted_until' => $user->restricted_until,
            ], 403);
        }

        return $next($request);
    }
}
