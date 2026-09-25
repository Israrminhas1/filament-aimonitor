<?php

namespace Filament\AiMonitor\Commands;

use Filament\AiMonitor\Models\AiModelPricing;
use Illuminate\Console\Command;

class SeedPricingCommand extends Command
{
    protected $signature = 'ai-monitor:setup-pricing {--force : Overwrite existing pricing}';

    protected $description = 'Setup default AI model pricing data';

    /**
     * Standard (non-batch, non-cached) list prices in USD per 1M tokens.
     * Prices change often — verify against each provider's pricing page.
     *
     * Model names are matched by prefix, so `gpt-4o` also prices `gpt-4o-2024-08-06`.
     *
     * @var array<int, array{0: string, 1: string, 2: float, 3: float}>
     */
    protected array $defaultPricing = [
        // Anthropic
        ['anthropic', 'claude-fable-5-1', 10.00, 50.00],
        ['anthropic', 'claude-fable-5', 10.00, 50.00],
        ['anthropic', 'claude-opus-5-5', 4.00, 20.00],
        ['anthropic', 'claude-opus-5', 5.00, 25.00],
        ['anthropic', 'claude-opus-4-8', 5.00, 25.00],
        ['anthropic', 'claude-opus-4-7', 5.00, 25.00],
        ['anthropic', 'claude-opus-4-6', 5.00, 25.00],
        ['anthropic', 'claude-sonnet-5', 2.00, 10.00],
        ['anthropic', 'claude-sonnet-4-6', 3.00, 15.00],
        ['anthropic', 'claude-haiku-4-5', 1.00, 5.00],

        // OpenAI
        ['openai', 'gpt-5', 1.25, 10.00],
        ['openai', 'gpt-5-mini', 0.25, 2.00],
        ['openai', 'gpt-5-nano', 0.05, 0.40],
        ['openai', 'gpt-4.1', 2.00, 8.00],
        ['openai', 'gpt-4.1-mini', 0.40, 1.60],
        ['openai', 'gpt-4.1-nano', 0.10, 0.40],
        ['openai', 'gpt-4o', 2.50, 10.00],
        ['openai', 'gpt-4o-mini', 0.15, 0.60],

        // Gemini
        ['gemini', 'gemini-2.5-pro', 1.25, 10.00],
        ['gemini', 'gemini-2.5-flash', 0.30, 2.50],
        ['gemini', 'gemini-2.5-flash-lite', 0.10, 0.40],

        // Perplexity
        ['perplexity', 'sonar-pro', 3.00, 15.00],
        ['perplexity', 'sonar', 1.00, 1.00],
    ];

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->defaultPricing as [$provider, $model, $inputPerMillion, $outputPerMillion]) {
            $existing = AiModelPricing::query()
                ->where('provider', $provider)
                ->where('model', $model)
                ->first();

            if ($existing && ! $force) {
                $skipped++;

                continue;
            }

            $attributes = [
                'input_per_1k' => $inputPerMillion / 1000,
                'output_per_1k' => $outputPerMillion / 1000,
                'is_default' => false,
                'is_fallback' => false,
                'active' => true,
            ];

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                AiModelPricing::create(['provider' => $provider, 'model' => $model] + $attributes);
                $created++;
            }
        }

        $this->components->info("Created {$created} and updated {$updated} pricing entries.");

        if ($skipped > 0) {
            $this->components->warn("Skipped {$skipped} existing entries. Use --force to overwrite them.");
        }

        return self::SUCCESS;
    }
}
