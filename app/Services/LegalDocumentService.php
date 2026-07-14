<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LegalDocumentService
{
    /**
     * Publish a draft: exactly one published version per document — the
     * current published version (if any) is archived in the same transaction.
     */
    public function publish(LegalDocumentVersion $version, User $publisher): LegalDocumentVersion
    {
        return DB::transaction(function () use ($version, $publisher) {
            LegalDocumentVersion::where('legal_document_id', $version->legal_document_id)
                ->where('status', 'published')
                ->where('id', '!=', $version->id)
                ->update(['status' => 'archived']);

            $version->update([
                'status' => 'published',
                'published_by' => $publisher->id,
                'published_at' => now(),
            ]);

            return $version->fresh();
        });
    }

    /** Copy any version's content into a fresh draft (restore / duplicate). */
    public function duplicateAsDraft(LegalDocumentVersion $source, User $author, ?string $newVersion = null): LegalDocumentVersion
    {
        return LegalDocumentVersion::create([
            'legal_document_id' => $source->legal_document_id,
            'version' => $newVersion ?: $this->suggestNextVersion($source->document),
            'changelog' => 'Draft based on v' . $source->version . ' — describe your changes before publishing.',
            'content_md' => $source->content_md,
            'status' => 'draft',
            'author_id' => $author->id,
        ]);
    }

    /** Next patch-level suggestion based on the highest existing version string. */
    public function suggestNextVersion(LegalDocument $document): string
    {
        $latest = $document->versions()
            ->orderByDesc('id')
            ->value('version');

        if (!$latest || !preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $latest, $m)) {
            return '1.0.0';
        }

        return $m[1] . '.' . ((int) $m[2] + 1) . '.0';
    }
}
