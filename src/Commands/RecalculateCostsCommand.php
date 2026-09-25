<?php

namespace Filament\AiMonitor\Commands;

use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Services\AiPricingService;
use Illuminate\Console\Command;

class RecalculateCostsCommand extends Command
{
    protected $signature = 'ai-monitor:recalculate-costs
        {--all : Recalculate every request, not only those missing a cost}
        {--include-manual : Also overwrite costs that were logged manually (with --all)}
        {--since= : Only requests that occurred on or after this date (e.g. 2026-01-01)}';

    protected $description = 'Recalculate AI request costs from the current pricing table';

    public function handle(AiPricingService $pricing): int
    {
        $query = AiRequest::query()
            ->when(! $this->option('all'), fn ($query) => $query->missingCost())
            ->when($this->option('since'), fn ($query, $since) => $query->where('occurred_at', '>=', $since));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->components->info('No requests to recalculate.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('include-manual');
        $updated = 0;

        $bar = $this->output->createProgressBar($total);

        $query->chunkById(500, function ($requests) use ($pricing, $force, $bar, &$updated) {
            foreach ($requests as $request) {
                $updated += (int) $pricing->recalculate($request, $force);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->components->info("Recalculated {$updated} of {$total} requests.");

        if ($updated < $total) {
            $this->components->warn(($total - $updated) . ' requests were skipped (no matching pricing, or a manually logged cost).');
        }

        return self::SUCCESS;
    }
}
