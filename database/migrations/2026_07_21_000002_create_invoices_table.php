<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->string('status', 20)->default('draft'); // draft | issued | void
            $table->foreignId('sponsored_ad_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_name', 150);
            $table->string('buyer_contact_name', 120)->nullable();
            $table->string('buyer_email', 150)->nullable();
            $table->string('buyer_address', 255)->nullable();
            $table->text('description');
            $table->decimal('amount', 8, 2);
            // Written only at issuance -- a frozen snapshot, not a live join.
            $table->string('vat_status', 20)->nullable();
            $table->decimal('net_amount', 8, 2)->nullable();
            $table->decimal('vat_amount', 8, 2)->nullable();
            $table->json('issuer_snapshot')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 300)->nullable();
            $table->text('notes')->nullable(); // internal only, never rendered on the PDF
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // Seed the numbering counter here, before the table can possibly see
        // any concurrent access -- locking a counter row that doesn't exist
        // yet locks nothing, so the row must already exist by the time the
        // app is live, not be created lazily on first use.
        DB::table('app_settings')->insertOrIgnore([
            'key' => 'invoice_number_next',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        DB::table('app_settings')->where('key', 'invoice_number_next')->delete();
    }
};
