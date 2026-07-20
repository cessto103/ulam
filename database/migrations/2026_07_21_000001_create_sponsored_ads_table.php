<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsored_ads', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 120);
            $table->string('company_name', 120);
            $table->string('tagline', 150)->nullable();
            $table->string('description', 300)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('cta_label', 40)->nullable(); // blank defaults to "Learn More" in the controller
            $table->decimal('amount_paid', 8, 2);
            $table->date('payment_received_at')->nullable(); // bookkeeping date, independent of the flight dates below
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_enabled')->default(false); // off by default; admin flips on when ready
            $table->boolean('show_to_free')->default(true);
            $table->boolean('show_to_premium')->default(true);
            $table->boolean('show_in_recipe_feed')->default(true);
            $table->boolean('show_in_community_feed')->default(true);
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamps();

            // Powers both the admin status filter and the mobile /ads/feed
            // eligibility query -- "currently running" is always computed on
            // read (SponsoredAd::isCurrentlyRunning()), never stored/synced.
            $table->index(['is_enabled', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsored_ads');
    }
};
