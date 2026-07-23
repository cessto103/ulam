<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PriceIntelligenceService;
use Illuminate\Console\Command;

class RefreshGovernmentPrices extends Command
{
    protected $signature   = 'prices:refresh-gov {--region= : Refresh a single region only}';
    protected $description = 'Refresh DA Bantay Presyo and DTI SRP reference prices using Claude web search';

    public function handle(PriceIntelligenceService $service): int
    {
        if ($service->aiDisabled()) {
            $this->warn('AI price refresh is disabled (price_refresh_ai_enabled = 0) — skipping.');
            return self::SUCCESS;
        }

        $singleRegion = $this->option('region');

        $regions = $singleRegion
            ? collect([$singleRegion])
            : User::whereNotNull('region')->distinct()->pluck('region');

        if ($regions->isEmpty()) {
            $this->warn('No regions found to refresh.');
            return self::FAILURE;
        }

        $total = 0;
        foreach ($regions as $region) {
            $this->info("Refreshing government price references: {$region}...");
            try {
                $count  = $service->refreshGovernmentPrices($region);
                $total += $count;
                $this->line("  → {$count} reference prices saved");
            } catch (\Throwable $e) {
                $this->error("  → Failed: {$e->getMessage()}");
            }

            if (!$singleRegion) {
                sleep(2);
            }
        }

        $this->info("Done. Total reference prices saved: {$total}");
        return self::SUCCESS;
    }
}
