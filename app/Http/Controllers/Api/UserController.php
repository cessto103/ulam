<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SecondaryEmailOtpMail;
use App\Models\Task;
use App\Models\UserTask;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'                           => $user->id,
            'name'                         => $user->name,
            'username'                     => $user->username,
            'email'                        => $user->email,
            'email_verified_at'            => $user->email_verified_at,
            'secondary_email'              => $user->secondary_email,
            'secondary_email_verified_at'  => $user->secondary_email_verified_at,
            'avatar'                       => $user->avatar,
            'bio'                          => $user->bio,
            'plan'                         => $user->plan,
            'is_premium'                   => $user->isPremium(),
            'premium_expires_at'           => $user->premium_expires_at,
            'premium_source'               => $user->premium_source,
            'household_size'               => $user->household_size,
            'barangay'                     => $user->barangay,
            'municipality'                 => $user->municipality,
            'province'                     => $user->province,
            'region'                       => $user->region,
            'latitude'                     => $user->latitude,
            'longitude'                    => $user->longitude,
            'dietary_preferences'          => $user->dietary_preferences,
            'xp'                           => $user->xp,
            'level'                        => $user->level,
            'streak_days'                  => $user->streak_days,
            'ai_meal_plans_used_this_month' => $user->ai_meal_plans_used_this_month,
            'ai_plans_remaining'           => $user->isPremium() ? null : 0,
            'onboarding_completed'          => (bool) $user->onboarding_completed,
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'max:3072']]);

        $user = $request->user();

        if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
        }

        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
        $url  = '/storage/' . $path;
        $user->update(['avatar' => $url]);

        \App\Jobs\ModerateImageJob::dispatchAfterResponse($url, 'user.avatar', $user->id);

        return response()->json(['avatar' => $url]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'                 => $user->id,
                'name'               => $user->name,
                'username'           => $user->username,
                'email'              => $user->email,
                'avatar'             => $user->avatar,
                'bio'                => $user->bio,
                'plan'               => $user->plan,
                'is_premium'         => $user->isPremium(),
                'premium_expires_at' => $user->premium_expires_at,
                'premium_source'     => $user->premium_source,
                'household_size'     => $user->household_size,
                'barangay'           => $user->barangay,
                'municipality'       => $user->municipality,
                'province'           => $user->province,
                'region'             => $user->region,
                'dietary_preferences' => $user->dietary_preferences,
                'xp'                 => $user->xp,
                'level'              => $user->level,
                'streak_days'        => $user->streak_days,
                'ai_plans_remaining' => $user->isPremium() ? null : 0,
                'created_at'         => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'                   => ['sometimes', 'string', 'max:255'],
            'username'               => ['sometimes', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', "unique:users,username,{$request->user()->id}"],
            'bio'                    => ['nullable', 'string', 'max:300'],
            'household_size'         => ['nullable', 'integer', 'min:1', 'max:20'],
            'barangay'               => ['nullable', 'string', 'max:100'],
            'municipality'           => ['nullable', 'string', 'max:100'],
            'province'               => ['nullable', 'string', 'max:100'],
            'region'                 => ['nullable', 'string', 'max:100'],
            'latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'dietary_preferences'    => ['nullable', 'array'],
            'dietary_preferences.*'  => ['string', 'max:50'],
            'onboarding_completed'   => ['sometimes', 'boolean'],
        ]);

        $request->user()->update($validated);

        return response()->json(['user' => $request->user()->fresh()]);
    }

    public function requestSecondaryEmail(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required', 'email', 'max:255',
                Rule::notIn([$user->email]),
                Rule::unique('users', 'email'),
                Rule::unique('users', 'secondary_email')->ignore($user->id)->where(
                    fn ($q) => $q->whereNotNull('secondary_email_verified_at')
                ),
            ],
        ], [
            'email.not_in' => 'This is already your primary email.',
        ]);

        $code = (string) random_int(100000, 999999);

        $user->update([
            'secondary_email' => $validated['email'],
            'secondary_email_verified_at' => null,
            'secondary_email_otp' => $code,
            'secondary_email_otp_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($validated['email'])->send(new SecondaryEmailOtpMail($user, $code));

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function verifySecondaryEmail(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $user->secondary_email || ! $user->secondary_email_otp) {
            throw ValidationException::withMessages(['code' => 'No pending verification. Please request a new code.']);
        }

        if ($user->secondary_email_otp_expires_at?->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Please request a new one.']);
        }

        if (! hash_equals($user->secondary_email_otp, $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'Incorrect code.']);
        }

        $user->update([
            'secondary_email_verified_at' => now(),
            'secondary_email_otp' => null,
            'secondary_email_otp_expires_at' => null,
        ]);

        return response()->json(['user' => $user->fresh()]);
    }

    public function removeSecondaryEmail(Request $request)
    {
        $request->user()->update([
            'secondary_email' => null,
            'secondary_email_verified_at' => null,
            'secondary_email_otp' => null,
            'secondary_email_otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Secondary email removed.']);
    }

    /**
     * Old shape kept as a compatibility shim -- backed by Task/UserTask now,
     * but the mobile app doesn't change until Phase 4 of the gamification
     * revamp lands. Every 'once'-frequency task (both single achievements
     * and all 4 rows of a tier group) shows up flat, same as before.
     */
    public function achievements(Request $request)
    {
        $user = $request->user();

        $earned = UserTask::where('user_id', $user->id)
            ->where('is_completed', true)
            ->pluck('completed_at', 'task_id');

        $all = Task::where('is_active', true)
            ->where('frequency', 'once')
            ->orderBy('xp_reward')
            ->get()
            ->map(function ($task) use ($earned, $user) {
                return [
                    'id'              => $task->id,
                    'slug'            => $task->slug,
                    'title'           => Task::displayTitle($task, $user->gender, 'tl'),
                    'title_en'        => Task::displayTitle($task, $user->gender, 'en'),
                    'description'     => $task->description,
                    'description_en'  => $task->description_en,
                    'icon'            => $task->icon,
                    'xp_reward'       => $task->xp_reward,
                    'tier'            => $task->tier,
                    'tier_group'      => $task->tier_group,
                    'is_active'       => $task->is_active,
                    'is_earned'       => $earned->has($task->id),
                    'earned_at'       => $earned[$task->id] ?? null,
                ];
            });

        return response()->json(['achievements' => $all]);
    }

    /** Old shape kept as a compatibility shim, see achievements() above. */
    public function dailyTasks(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $tasks = Task::where('is_active', true)->whereIn('frequency', ['daily', 'weekly'])->get();

        $completedDates = UserTask::where('user_id', $user->id)
            ->whereIn('period_date', [$today, $weekStart])
            ->where('is_completed', true)
            ->pluck('period_date', 'task_id');

        $result = $tasks->map(function ($task) use ($completedDates, $today, $weekStart) {
            $periodDate = $task->frequency === 'weekly' ? $weekStart : $today;
            $completedFor = $completedDates[$task->id] ?? null;

            return [
                'id'           => $task->id,
                'title'        => $task->title,
                'description'  => $task->description,
                'icon'         => $task->icon,
                'xp_reward'    => $task->xp_reward,
                'frequency'    => $task->frequency,
                'is_completed' => $completedFor && $completedFor->toDateString() === $periodDate,
            ];
        });

        return response()->json(['tasks' => $result]);
    }

    /**
     * Unified replacement for achievements()/dailyTasks() above -- not
     * consumed by the mobile app until Phase 4 of the gamification revamp,
     * added now since the underlying query logic is already here.
     */
    public function tasks(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $repeating = Task::where('is_active', true)
            ->whereIn('frequency', ['daily', 'weekly', 'monthly'])
            ->get();

        $completedPeriods = UserTask::where('user_id', $user->id)
            ->whereIn('period_date', [$today, $weekStart, $monthStart])
            ->where('is_completed', true)
            ->pluck('period_date', 'task_id');

        $mapRepeating = function ($task) use ($completedPeriods, $today, $weekStart, $monthStart) {
            $periodDate = match ($task->frequency) {
                'weekly'  => $weekStart,
                'monthly' => $monthStart,
                default   => $today,
            };
            $completedFor = $completedPeriods[$task->id] ?? null;

            return [
                'id'           => $task->id,
                'title'        => $task->title,
                'description'  => $task->description,
                'icon'         => $task->icon,
                'xp_reward'    => $task->xp_reward,
                'frequency'    => $task->frequency,
                'is_completed' => $completedFor && $completedFor->toDateString() === $periodDate,
            ];
        };

        $onceTasks = Task::where('is_active', true)->where('frequency', 'once')->get();
        $earned = UserTask::where('user_id', $user->id)
            ->where('is_completed', true)
            ->pluck('completed_at', 'task_id');

        $single = $onceTasks->whereNull('tier_group')->map(function ($task) use ($earned, $user) {
            return [
                'id'          => $task->id,
                'title'       => Task::displayTitle($task, $user->gender, 'tl'),
                'title_en'    => Task::displayTitle($task, $user->gender, 'en'),
                'description' => $task->description,
                'icon'        => $task->icon,
                'xp_reward'   => $task->xp_reward,
                'is_earned'   => $earned->has($task->id),
                'earned_at'   => $earned[$task->id] ?? null,
            ];
        })->values();

        $xpService = app(XpService::class);
        $tierGroups = $onceTasks->whereNotNull('tier_group')
            ->groupBy('tier_group')
            ->map(function ($tierTasks) use ($earned, $user, $xpService) {
                $first = $tierTasks->first();
                $actualCount = $xpService->metricCount($user, $first->action_type);
                $currentTier = null;

                $tiers = $tierTasks->sortBy('target_count')->values()->map(function ($task) use ($earned, &$currentTier) {
                    $isEarned = $earned->has($task->id);
                    if ($isEarned) $currentTier = $task->tier;

                    return [
                        'tier'         => $task->tier,
                        'target_count' => $task->target_count,
                        'xp_reward'    => $task->xp_reward,
                        'is_earned'    => $isEarned,
                        'earned_at'    => $earned[$task->id] ?? null,
                    ];
                });

                return [
                    'tier_group'   => $first->tier_group,
                    'title'        => Task::displayTitle($first, $user->gender, 'tl'),
                    'title_en'     => Task::displayTitle($first, $user->gender, 'en'),
                    'icon'         => $first->icon,
                    'actual_count' => $actualCount,
                    'current_tier' => $currentTier,
                    'tiers'        => $tiers,
                ];
            })->values();

        return response()->json([
            'daily'   => $repeating->where('frequency', 'daily')->map($mapRepeating)->values(),
            'weekly'  => $repeating->where('frequency', 'weekly')->map($mapRepeating)->values(),
            'monthly' => $repeating->where('frequency', 'monthly')->map($mapRepeating)->values(),
            'once'    => [
                'single'      => $single,
                'tier_groups' => $tierGroups,
            ],
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $totalSaved = $user->budgetPeriods()
            ->with('dailyLogs')
            ->get()
            ->flatMap->dailyLogs
            ->where('saved_amount', '>', 0)
            ->sum('saved_amount');

        return response()->json([
            'stats' => [
                'total_saved'          => round($totalSaved, 2),
                'meal_plans_generated' => $user->mealPlans()->count(),
                'posts_count'          => $user->posts()->count(),
                'xp'                   => $user->xp,
                'level'                => $user->level,
                'streak_days'          => $user->streak_days,
                'achievements_count'   => UserTask::where('user_id', $user->id)
                    ->where('is_completed', true)
                    ->whereHas('task', fn ($q) => $q->where('frequency', 'once'))
                    ->count(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['users' => []]);
        }

        $me = $request->user();

        $followingIds = \App\Models\Connection::where('requester_id', $me->id)
            ->where('status', 'connected')
            ->pluck('recipient_id');

        $users = \App\Models\User::where('id', '!=', $me->id)
            ->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('username', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'username', 'avatar', 'municipality', 'level')
            ->limit(20)
            ->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'username'     => $u->username,
                'avatar'       => $u->avatar,
                'municipality' => $u->municipality,
                'level'        => $u->level,
                'is_following' => $followingIds->contains($u->id),
            ]);

        return response()->json(['users' => $users]);
    }

    public function leaderboard(Request $request)
    {
        $user       = $request->user();
        $scope      = $request->get('scope', 'municipality');
        $scopeValue = $scope === 'barangay' ? $user->barangay : $user->municipality;

        // Fall back to municipality scope if barangay is unset
        if (! $scopeValue && $scope === 'barangay') {
            $scope      = 'municipality';
            $scopeValue = $user->municipality;
        }

        if (! $scopeValue) {
            // No location set — return global top-10 by XP
            $scopeValue = null;
        }

        $query = \App\Models\User::select('id', 'name', 'username', 'avatar', 'level', 'xp', 'municipality')
            ->orderByDesc('xp')
            ->limit(10);

        if ($scopeValue) {
            $query->where($scope === 'barangay' ? 'barangay' : 'municipality', $scopeValue);
        }

        $topUsers   = $query->get();
        $myRankRow  = $topUsers->search(fn ($u) => $u->id === $user->id);

        // If user isn't in top-10, find their actual rank
        $myRank = $myRankRow !== false ? $myRankRow + 1 : DB::table('users')
            ->when($scopeValue, fn ($q) => $q->where($scope === 'barangay' ? 'barangay' : 'municipality', $scopeValue))
            ->where('xp', '>', $user->xp)
            ->count() + 1;

        return response()->json([
            'leaderboard' => $topUsers->map(fn ($u, $i) => [
                'rank'     => $i + 1,
                'user'     => $u,
                'xp'       => $u->xp,
                'is_me'    => $u->id === $user->id,
            ]),
            'my_rank'     => $myRank,
            'scope'        => $scope,
            'scope_value'  => $scopeValue,
        ]);
    }
}
