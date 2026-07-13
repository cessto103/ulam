<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('value_type', 20)->default('boolean');
            $table->timestamps();
        });

        Schema::create('seller_plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value', 255);
            $table->timestamps();
            $table->unique(['seller_plan_id', 'feature_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_plan_price_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('paymongo');
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->string('status', 24)->default('pending');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'current_period_end']);
            $table->index(['status', 'grace_ends_at']);
        });

        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_plan_price_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('paymongo');
            $table->string('provider_session_id')->nullable()->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('PHP');
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('checkout_session_id')->nullable()->after('subscription_id')->constrained()->nullOnDelete();
            $table->string('failure_code', 80)->nullable()->after('status');
            $table->text('failure_message')->nullable()->after('failure_code');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('provider_attempt_id')->nullable()->unique();
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('PHP');
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('provider_event_id')->unique();
            $table->string('event_type', 100);
            $table->boolean('livemode')->default(false);
            $table->string('status', 24)->default('received');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['provider', 'event_type', 'status']);
        });

        Schema::create('billing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100);
            $table->string('actor_type', 30)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['subscription_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        $features = [
            ['key' => 'store_boost', 'name' => 'Store boosting', 'value_type' => 'boolean'],
            ['key' => 'advanced_analytics', 'name' => 'Advanced analytics', 'value_type' => 'boolean'],
            ['key' => 'priority_support', 'name' => 'Priority support', 'value_type' => 'boolean'],
        ];
        foreach ($features as $feature) {
            DB::table('features')->insert(array_merge($feature, ['created_at' => now(), 'updated_at' => now()]));
        }

        // Preserve active manual subscriptions while PayMongo checkout is rolled out.
        DB::table('ad_subscriptions')->where('type', 'tindahan_listing')
            ->whereIn('status', ['active', 'pending'])->orderBy('id')->each(function ($legacy) {
                $planId = DB::table('seller_plans')->where('slug', $legacy->plan)->value('id');
                if (! $planId) return;
                $priceId = DB::table('seller_plan_prices')->where('seller_plan_id', $planId)
                    ->where('duration', $legacy->duration ?: '1m')->value('id');
                DB::table('subscriptions')->insert([
                    'user_id' => $legacy->user_id,
                    'seller_plan_id' => $planId,
                    'seller_plan_price_id' => $priceId,
                    'provider' => $legacy->payment_method ?: 'gcash_manual',
                    'status' => $legacy->status,
                    'current_period_start' => $legacy->starts_at,
                    'current_period_end' => $legacy->expires_at,
                    'metadata' => json_encode(['legacy_ad_subscription_id' => $legacy->id]),
                    'created_at' => $legacy->created_at,
                    'updated_at' => $legacy->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_logs');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payment_attempts');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_session_id');
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn(['failure_code', 'failure_message', 'refunded_at']);
        });
        Schema::dropIfExists('checkout_sessions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('seller_plan_features');
        Schema::dropIfExists('features');
    }
};
