<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateStreak
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($user = $request->user()) {
            $today      = today()->toDateString();
            $lastActive = $user->last_active_date?->toDateString();

            if ($lastActive !== $today) {
                $yesterday = today()->subDay()->toDateString();
                $newStreak = $lastActive === $yesterday ? $user->streak_days + 1 : 1;

                $user->update([
                    'last_active_date' => $today,
                    'streak_days'      => $newStreak,
                ]);
            }
        }

        return $response;
    }
}
