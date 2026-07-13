<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tier catalog — includes the 'free' row so every limit (even the free
        // 10-item cap) is editable from the admin dashboard, not hardcoded.
        Schema::create('seller_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique(); // free | basic | suki | negosyante
            $table->string('name', 50);
            $table->string('tagline')->nullable();
            $table->unsignedSmallInteger('max_stores');
            $table->unsignedSmallInteger('max_items_per_store');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('seller_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_plan_id')->constrained()->cascadeOnDelete();
            $table->string('duration', 10); // 7d | 15d | 1m | 1y
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['seller_plan_id', 'duration']);
        });

        // Boost price list (sold in Phase 3, priced/editable from Phase 1).
        Schema::create('boost_options', function (Blueprint $table) {
            $table->id();
            $table->string('target', 20); // tindahan | recipe
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['target', 'duration_days']);
        });

        // ad_subscriptions predates the tier design — widen its enums to strings
        // so plan slugs (suki/negosyante), statuses (rejected/refunded) and future
        // payment methods don't need DDL each time. MySQL-only raw DDL (WAMP dev +
        // prod target are MySQL; the enum→varchar change has no sqlite equivalent
        // short of a table rebuild).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_subscriptions MODIFY type VARCHAR(30) NOT NULL DEFAULT 'tindahan_listing'");
            DB::statement("ALTER TABLE ad_subscriptions MODIFY plan VARCHAR(30) NOT NULL DEFAULT 'basic'");
            DB::statement("ALTER TABLE ad_subscriptions MODIFY payment_method VARCHAR(30) NOT NULL DEFAULT 'gcash_manual'");
            DB::statement("ALTER TABLE ad_subscriptions MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        Schema::table('ad_subscriptions', function (Blueprint $table) {
            $table->string('duration', 10)->nullable()->after('plan'); // 7d | 15d | 1m | 1y
            $table->string('rejected_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejected_reason');
            $table->timestamp('refunded_at')->nullable()->after('reviewed_at');
            // One GCash reference number can only ever activate one subscription.
            $table->unique('payment_reference');
        });

        // Stores hidden because the owner's tier no longer covers them (downgrade,
        // refund). Separate from is_active, which owners/admins control directly.
        Schema::table('tindahan', function (Blueprint $table) {
            $table->boolean('hidden_by_plan')->default(false)->after('is_active');
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('category', 30)->default('other'); // payment | subscription | store | account | bug | other
            $table->string('status', 15)->default('open');    // open | answered | closed
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_reply_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_from_admin')->default(false);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('question_tl')->nullable();
            $table->text('answer');
            $table->text('answer_tl')->nullable();
            $table->string('category', 30)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');

        Schema::table('tindahan', function (Blueprint $table) {
            $table->dropColumn('hidden_by_plan');
        });

        Schema::table('ad_subscriptions', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn(['duration', 'rejected_reason', 'reviewed_at', 'refunded_at']);
        });

        Schema::dropIfExists('boost_options');
        Schema::dropIfExists('seller_plan_prices');
        Schema::dropIfExists('seller_plans');
    }
};
