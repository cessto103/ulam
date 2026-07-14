<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\UserLegalAcceptance;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    /**
     * GET /legal/status — every published document + whether the current
     * user has accepted its latest version. The app's acceptance gate
     * prompts for anything with accepted=false.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $docs = LegalDocument::with('publishedVersion')->get()
            ->filter(fn ($d) => $d->publishedVersion)
            ->map(function ($d) use ($user) {
                $accepted = UserLegalAcceptance::where('user_id', $user->id)
                    ->where('legal_document_version_id', $d->publishedVersion->id)
                    ->exists();

                return [
                    'slug' => $d->slug,
                    'title' => $d->title,
                    'version' => $d->publishedVersion->version,
                    'published_at' => $d->publishedVersion->published_at,
                    'accepted' => $accepted,
                ];
            })
            ->values();

        return response()->json(['documents' => $docs]);
    }

    /** GET /legal/{slug} — the published version's content. */
    public function show(string $slug)
    {
        $doc = LegalDocument::where('slug', $slug)->with('publishedVersion')->firstOrFail();

        abort_if(!$doc->publishedVersion, 404);

        return response()->json([
            'slug' => $doc->slug,
            'title' => $doc->title,
            'version' => $doc->publishedVersion->version,
            'published_at' => $doc->publishedVersion->published_at,
            'content_md' => $doc->publishedVersion->content_md,
        ]);
    }

    /** POST /legal/{slug}/accept — records acceptance of the current published version. */
    public function accept(Request $request, string $slug)
    {
        $doc = LegalDocument::where('slug', $slug)->with('publishedVersion')->firstOrFail();

        abort_if(!$doc->publishedVersion, 404);

        UserLegalAcceptance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'legal_document_version_id' => $doc->publishedVersion->id,
            ],
            [
                'legal_document_id' => $doc->id,
                'accepted_at' => now(),
                'ip' => $request->ip(),
                'device' => substr((string) $request->userAgent(), 0, 255) ?: null,
            ]
        );

        return response()->json(['message' => 'Accepted.', 'version' => $doc->publishedVersion->version]);
    }
}
