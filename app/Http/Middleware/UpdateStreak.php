<?php

namespace App\Http\Middleware;

use App\Services\PremiumTrialService;
use Closure;
use Illuminate\Http\Request;

class UpdateStreak
{
    public function __construct(private PremiumTrialService $premiumTrials)
    {
    }

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

                // Fires only on the day a milestone is first reached, not every day after.
                $this->premiumTrials->grantForStreak($user, $newStreak);
            }
        }

        return $response;
    }
}
