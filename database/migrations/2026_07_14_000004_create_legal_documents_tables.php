<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per document type — future docs (cookie policy, seller
        // agreement, community guidelines...) are just new rows.
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique(); // terms | privacy | ...
            $table->string('title');
            $table->timestamps();
        });

        // Content lives on versions; exactly one 'published' per document,
        // enforced by LegalDocumentService (publish archives the predecessor).
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_document_id')->constrained()->cascadeOnDelete();
            $table->string('version', 20);            // e.g. 1.0.0
            $table->text('changelog');                 // required "What's changed"
            $table->longText('content_md');            // markdown source of truth
            $table->string('status', 15)->default('draft'); // draft | published | archived
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['legal_document_id', 'status']);
        });

        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_version_id')->constrained('legal_document_versions')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->string('ip', 45)->nullable();
            $table->string('device')->nullable();

            // One acceptance row per user per version.
            $table->unique(['user_id', 'legal_document_version_id'], 'user_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_legal_acceptances');
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }
};
