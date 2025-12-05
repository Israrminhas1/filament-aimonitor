<?php

namespace Filament\AiMonitor\Commands;

use Illuminate\Console\Command;
use Filament\AiMonitor\Models\AiModelPricing;

class SeedPricingCommand extends Command
{
    protected $signature = 'ai-monitor:setup-pricing {--force : Overwrite existing pricing}';

    protected $description = 'Setup default AI model pricing data';

    protected array $defaultPricing = [
        // OpenAI
        ['provider' => 'openai', 'model' => 'gpt-4o', 'input_per_1k' => 0.005, 'output_per_1k' => 0.015],
        ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'input_per_1k' => 0.00015, 'output_per_1k' => 0.0006],
        ['provider' => 'openai', 'model' => 'gpt-4-turbo', 'input_per_1k' => 0.01, 'output_per_1k' => 0.03],
        ['provider' => 'openai', 'model' => 'gpt-3.5-turbo', 'input_per_1k' => 0.0005, 'output_per_1k' => 0.0015],

        // Anthropic
        ['provider' => 'anthropic', 'model' => 'claude-3-5-sonnet', 'input_per_1k' => 0.003, 'output_per_1k' => 0.015],
        ['provider' => 'anthropic', 'model' => 'claude-3-opus', 'input_per_1k' => 0.015, 'output_per_1k' => 0.075],
        ['provider' => 'anthropic', 'model' => 'claude-3-haiku', 'input_per_1k' => 0.00025, 'output_per_1k' => 0.00125],

        // Gemini
        ['provider' => 'gemini', 'model' => 'gemini-1.5-pro', 'input_per_1k' => 0.00125, 'output_per_1k' => 0.005],
        ['provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'input_per_1k' => 0.000075, 'output_per_1k' => 0.0003],

        // Perplexity
        ['provider' => 'perplexity', 'model' => 'sonar-pro', 'input_per_1k' => 0.003, 'output_per_1k' => 0.015],
        ['provider' => 'perplexity', 'model' => 'sonar', 'input_per_1k' => 0.001, 'output_per_1k' => 0.001],
    ];

    public function handle(): int
    {
        $force = $this->option('force');
        $created = 0;
        $skipped = 0;

        foreach ($this->defaultPricing as $pricing) {
            $exists = AiModelPricing::where('provider', $pricing['provider'])
                ->where('model', $pricing['model'])
                ->exists();

            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            if ($exists && $force) {
                AiModelPricing::where('provider', $pricing['provider'])
                    ->where('model', $pricing['model'])
                    ->delete();
            }

            AiModelPricing::create([
                'provider' => $pricing['provider'],
                'model' => $pricing['model'],
                'input_per_1k' => $pricing['input_per_1k'],
                'output_per_1k' => $pricing['output_per_1k'],
                'active' => true,
            ]);

            $created++;
        }

        $this->info("Created {$created} pricing entries.");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} existing entries. Use --force to overwrite.");
        }

        return self::SUCCESS;
    }
}
