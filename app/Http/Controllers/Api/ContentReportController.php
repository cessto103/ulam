<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Post;
use App\Models\Recipe;
use App\Models\Tindahan;

class ContentReportController extends Controller
{
    /** File a report against a post, recipe, or store. One report per user per item. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'content_type' => ['required', Rule::in(['post', 'recipe', 'tindahan'])],
            'content_id'   => ['required', 'integer', 'min:1'],
            'reason'       => ['required', 'string', 'max:50'],
            'details'      => ['nullable', 'string', 'max:500'],
        ]);

        $model = match ($data['content_type']) {
            'post' => Post::class,
            'recipe' => Recipe::class,
            'tindahan' => Tindahan::class,
        };
        $content = $model::find($data['content_id']);
        abort_unless($content, 422, 'The reported content no longer exists.');

        $report = ContentReport::firstOrCreate(
            [
                'user_id'      => $request->user()->id,
                'content_type' => $data['content_type'],
                'content_id'   => $data['content_id'],
            ],
            [
                // Captured now, not resolved later -- if the content is deleted
                // before an admin reviews the report, this is still how the
                // right account gets warned/restricted/banned.
                'reported_user_id' => $content->user_id,
                'reason'  => $data['reason'],
                'details' => $data['details'] ?? null,
            ]
        );

        return response()->json([
            'message' => $report->wasRecentlyCreated
                ? 'Report submitted. Salamat sa pag-report!'
                : 'You already reported this.',
            'report' => $report,
        ], $report->wasRecentlyCreated ? 201 : 200);
    }
}
