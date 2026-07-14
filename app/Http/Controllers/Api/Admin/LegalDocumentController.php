<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\UserLegalAcceptance;
use App\Services\LegalDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalDocumentController extends Controller
{
    public function __construct(private LegalDocumentService $service)
    {
    }

    /** GET /admin/legal-documents — all documents + published version + acceptance counts. */
    public function index()
    {
        $docs = LegalDocument::with(['publishedVersion.publisher:id,name'])
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'slug' => $d->slug,
                'title' => $d->title,
                'published_version' => $d->publishedVersion?->version,
                'published_at' => $d->publishedVersion?->published_at,
                'published_by' => $d->publishedVersion?->publisher?->name,
                'acceptance_count' => $d->publishedVersion
                    ? UserLegalAcceptance::where('legal_document_version_id', $d->publishedVersion->id)->count()
                    : 0,
                'versions_count' => $d->versions()->count(),
                'suggested_next_version' => $this->service->suggestNextVersion($d),
            ]);

        return response()->json(['documents' => $docs]);
    }

    /** GET /admin/legal-documents/{slug}/versions — history, filterable by status/search. */
    public function versions(Request $request, string $slug)
    {
        $doc = LegalDocument::where('slug', $slug)->firstOrFail();

        $query = $doc->versions()
            ->with(['author:id,name', 'publisher:id,name'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $s = $request->string('search');
            $query->where(fn ($q) => $q->where('version', 'like', "%{$s}%")->orWhere('changelog', 'like', "%{$s}%"));
        }

        return response()->json([
            'document' => ['slug' => $doc->slug, 'title' => $doc->title],
            'versions' => $query->get()->map(fn ($v) => $this->formatVersion($v, false)),
        ]);
    }

    /** GET /admin/legal-versions/{id} — full content for view/compare. */
    public function showVersion(int $id)
    {
        $v = LegalDocumentVersion::with(['author:id,name', 'publisher:id,name', 'document:id,slug,title'])->findOrFail($id);

        return response()->json(['version' => $this->formatVersion($v, true)]);
    }

    /** POST /admin/legal-documents/{slug}/versions — new draft (blank or duplicated). */
    public function storeVersion(Request $request, string $slug)
    {
        $doc = LegalDocument::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:20', 'regex:/^\d+\.\d+\.\d+$/'],
            'duplicate_from' => ['nullable', 'integer', 'exists:legal_document_versions,id'],
        ]);

        if (!empty($validated['duplicate_from'])) {
            $source = LegalDocumentVersion::where('legal_document_id', $doc->id)->findOrFail($validated['duplicate_from']);
            $draft = $this->service->duplicateAsDraft($source, $request->user(), $validated['version'] ?? null);
        } else {
            $draft = $doc->versions()->create([
                'version' => $validated['version'] ?? $this->service->suggestNextVersion($doc),
                'changelog' => '',
                'content_md' => "# {$doc->title}\n\n",
                'status' => 'draft',
                'author_id' => $request->user()->id,
            ]);
        }

        return response()->json(['version' => $this->formatVersion($draft->load('author:id,name'), true)], 201);
    }

    /** PATCH /admin/legal-versions/{id} — edit a draft (autosave target). */
    public function updateVersion(Request $request, int $id)
    {
        $v = LegalDocumentVersion::findOrFail($id);

        if ($v->status !== 'draft') {
            return response()->json(['message' => 'Only drafts can be edited. Restore this version into a new draft instead.'], 422);
        }

        $validated = $request->validate([
            'version' => ['sometimes', 'string', 'max:20', 'regex:/^\d+\.\d+\.\d+$/'],
            'changelog' => ['sometimes', 'string', 'max:2000'],
            'content_md' => ['sometimes', 'string', 'max:200000'],
        ]);

        $v->update($validated);

        return response()->json(['version' => $this->formatVersion($v->fresh()->load('author:id,name'), true)]);
    }

    /** POST /admin/legal-versions/{id}/publish — archive predecessor, go live. */
    public function publish(Request $request, int $id)
    {
        $v = LegalDocumentVersion::findOrFail($id);

        if ($v->status === 'published') {
            return response()->json(['message' => 'This version is already published.'], 422);
        }

        if (trim($v->changelog) === '' || str_starts_with($v->changelog, 'Draft based on')) {
            return response()->json(['message' => "Fill in \"What's changed\" before publishing."], 422);
        }

        $v = $this->service->publish($v, $request->user());

        return response()->json(['message' => 'Published. Users will be asked to accept the new version.', 'version' => $this->formatVersion($v, true)]);
    }

    /** POST /admin/legal-versions/{id}/archive */
    public function archive(int $id)
    {
        $v = LegalDocumentVersion::findOrFail($id);

        if ($v->status === 'published') {
            return response()->json(['message' => 'Publish another version first — a document cannot be left with no published version.'], 422);
        }

        $v->update(['status' => 'archived']);

        return response()->json(['message' => 'Archived.']);
    }

    /** DELETE /admin/legal-versions/{id} — drafts only. */
    public function destroyVersion(int $id)
    {
        $v = LegalDocumentVersion::findOrFail($id);

        if ($v->status !== 'draft') {
            return response()->json(['message' => 'Only drafts can be deleted.'], 422);
        }

        $v->delete();

        return response()->json(['message' => 'Draft deleted.']);
    }

    private function formatVersion(LegalDocumentVersion $v, bool $withContent): array
    {
        $row = [
            'id' => $v->id,
            'document_slug' => $v->document->slug ?? null,
            'version' => $v->version,
            'changelog' => $v->changelog,
            'status' => $v->status,
            'author' => $v->author?->name,
            'published_by' => $v->publisher?->name,
            'published_at' => $v->published_at,
            'created_at' => $v->created_at,
            'updated_at' => $v->updated_at,
            'acceptance_count' => $v->status === 'published'
                ? UserLegalAcceptance::where('legal_document_version_id', $v->id)->count()
                : null,
        ];

        if ($withContent) {
            $row['content_md'] = $v->content_md;
        }

        return $row;
    }
}
