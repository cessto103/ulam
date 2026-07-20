<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ResolvesModerationReports;
use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\Recipe;
use App\Models\Tindahan;
use App\Models\UserStrike;
use App\Services\UserModerationService;
use Illuminate\Http\Request;

class ContentReportController extends Controller
{
    use ResolvesModerationReports;

    private const CONTENT_MODELS = [
        'post' => Post::class,
        'recipe' => Recipe::class,
        'tindahan' => Tindahan::class,
    ];

    public function __construct(private UserModerationService $moderation)
    {
    }

    public function index(Request $request)
    {
        $query = ContentReport::with(['reporter:id,name,username', 'reportedUser:id,name,username', 'resolvedBy:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('content_type')) {
            $query->where('content_type', $request->string('content_type'));
        }

        $reports = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        $reports->getCollection()->transform(fn ($report) => $this->withContentPreview($report));

        return response()->json($reports);
    }

    public function show(int $id)
    {
        $report = ContentReport::with(['reporter:id,name,username', 'reportedUser:id,name,username', 'resolvedBy:id,name'])->findOrFail($id);
        $report = $this->withContentPreview($report);
        $report->setAttribute('reported_user_strikes', $this->strikeSummary($report->reported_user_id));

        return response()->json(['report' => $report]);
    }

    public function destroy(int $id)
    {
        ContentReport::findOrFail($id)->delete();

        return response()->json(['message' => 'Report deleted.']);
    }

    public function warn(Request $request, int $id)
    {
        return $this->act($request, $id, fn ($user, $reason, $admin, $report) => $this->moderation->warn($user, $reason, $admin, contentReport: $report));
    }

    public function restrict(Request $request, int $id)
    {
        return $this->act($request, $id, fn ($user, $reason, $admin, $report) => $this->moderation->restrict($user, $reason, $admin, contentReport: $report));
    }

    public function ban(Request $request, int $id)
    {
        return $this->act($request, $id, fn ($user, $reason, $admin, $report) => $this->moderation->ban($user, $reason, $admin, contentReport: $report));
    }

    public function dismiss(Request $request, int $id)
    {
        $report = $this->pendingReportOrFail(ContentReport::class, $id);
        $this->resolveReport($report, $request, 'dismissed');

        return response()->json(['report' => $report->fresh()]);
    }

    /** Shared body for warn/restrict/ban -- guard, resolve the offending user, apply the action, resolve the report + its siblings. */
    private function act(Request $request, int $id, \Closure $apply)
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $report = $this->pendingReportOrFail(ContentReport::class, $id);
        $user = $report->reportedUser ?? \App\Models\User::find($report->reported_user_id);

        if (! $user) {
            return response()->json(['message' => 'The reported user no longer exists.'], 422);
        }

        $reason = $validated['reason'] ?? $report->reason . ($report->details ? ": {$report->details}" : '');

        $apply($user, $reason, $request->user(), $report);

        $this->resolveReport($report, $request);

        // Other pending reports against the SAME content are now moot --
        // auto-resolve them too. Pending reports against this user for
        // OTHER content are left alone; each incident is judged on its own.
        ContentReport::where('content_type', $report->content_type)
            ->where('content_id', $report->content_id)
            ->where('id', '!=', $report->id)
            ->where('status', 'pending')
            ->get()
            ->each(fn ($sibling) => $this->resolveReport($sibling, $request));

        return response()->json(['report' => $report->fresh(), 'message' => "Action applied to {$user->name}."]);
    }

    private function withContentPreview(ContentReport $report): ContentReport
    {
        $modelClass = self::CONTENT_MODELS[$report->content_type] ?? null;
        $content = $modelClass ? $modelClass::find($report->content_id) : null;

        $preview = match (true) {
            ! $content => null,
            $report->content_type === 'post' => \Illuminate\Support\Str::limit($content->body, 80),
            $report->content_type === 'recipe' => $content->title,
            $report->content_type === 'tindahan' => $content->name,
            default => null,
        };

        $report->setAttribute('content_preview', $preview);
        $report->setAttribute('content_exists', (bool) $content);

        return $report;
    }

    private function strikeSummary(?int $userId): array
    {
        if (! $userId) {
            return ['active_count' => 0, 'recent' => []];
        }

        $strikes = UserStrike::where('user_id', $userId)
            ->with('issuedBy:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $activeCount = UserStrike::where('user_id', $userId)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        return [
            'active_count' => $activeCount,
            'recent' => $strikes->map(fn ($s) => [
                'level' => $s->level,
                'level_label' => $s->level_label,
                'reason' => $s->reason,
                'issued_by' => $s->issuedBy?->name,
                'created_at' => $s->created_at,
                'expires_at' => $s->expires_at,
            ]),
        ];
    }
}
