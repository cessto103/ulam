<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Services\PriceIntelligenceService;
use Illuminate\Console\Command;

class RefreshPricesAI extends Command
{
    protected $signature   = 'prices:refresh-ai {--market= : Refresh a single market by ID}';
    protected $description = 'Refresh market prices using Claude web search';

    public function handle(PriceIntelligenceService $service): int
    {
        if ($service->aiDisabled()) {
            $this->warn('AI price refresh is disabled (price_refresh_ai_enabled = 0) — skipping.');
            return self::SUCCESS;
        }

        $singleId = $this->option('market');

        $markets = $singleId
            ? Market::where('is_active', true)->where('id', $singleId)->get()
            : Market::where('is_active', true)->get();

        if ($markets->isEmpty()) {
            $this->warn('No active markets found.');
            return self::FAILURE;
        }

        $total = 0;
        foreach ($markets as $market) {
            $this->info("Refreshing: {$market->name} ({$market->municipality})...");
            try {
                $count  = $service->refreshMarket($market);
                $total += $count;
                $this->line("  → {$count} prices saved");
            } catch (\Throwable $e) {
                $this->error("  → Failed: {$e->getMessage()}");
            }

            // Brief pause between markets to avoid rate-limiting
            if (!$singleId) {
                sleep(2);
            }
        }

        $this->info("Done. Total prices saved: {$total}");
        return self::SUCCESS;
    }
}
