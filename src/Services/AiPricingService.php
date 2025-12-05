<?php

namespace Filament\AiMonitor\Services;

use Filament\AiMonitor\Models\AiModelPricing;

class AiPricingService
{
    public function getPricing(string $provider, ?string $model = null): ?array
    {
        $provider = strtolower($provider);
        return $this->getPricingFromDatabase($provider, $model);
    }

    public function hasPricing(string $provider, ?string $model = null): bool
    {
        return $this->getPricing($provider, $model) !== null;
    }

    public function hasAnyPricing(): bool
    {
        return AiModelPricing::where('active', true)->exists();
    }

    protected function getPricingFromDatabase(string $provider, ?string $model = null): ?array
    {
        if ($model !== null) {
            $modelPricing = AiModelPricing::query()
                ->where('provider', $provider)
                ->where('model', $model)
                ->where('active', true)
                ->where('is_default', false)
                ->where('is_fallback', false)
                ->first();

            if ($modelPricing) {
                return [
                    'input_per_1k' => $modelPricing->input_per_1k,
                    'output_per_1k' => $modelPricing->output_per_1k,
                ];
            }
        }

        $defaultPricing = AiModelPricing::query()
            ->where('provider', $provider)
            ->where('is_default', true)
            ->where('active', true)
            ->first();

        if ($defaultPricing) {
            return [
                'input_per_1k' => $defaultPricing->input_per_1k,
                'output_per_1k' => $defaultPricing->output_per_1k,
            ];
        }

        $fallbackPricing = AiModelPricing::query()
            ->where('is_fallback', true)
            ->where('active', true)
            ->first();

        if ($fallbackPricing) {
            return [
                'input_per_1k' => $fallbackPricing->input_per_1k,
                'output_per_1k' => $fallbackPricing->output_per_1k,
            ];
        }

        return null;
    }

    public function calculateCost(
        string $provider,
        ?string $model,
        ?int $promptTokens,
        ?int $completionTokens
    ): ?float {
        $pricing = $this->getPricing($provider, $model);

        if ($pricing === null) {
            return null;
        }

        $promptTokens = $promptTokens ?? 0;
        $completionTokens = $completionTokens ?? 0;

        $inputCost = ($promptTokens / 1000) * $pricing['input_per_1k'];
        $outputCost = ($completionTokens / 1000) * $pricing['output_per_1k'];

        return round($inputCost + $outputCost, 6);
    }

    public function getProviders(): array
    {
        return AiModelPricing::query()
            ->where('active', true)
            ->select('provider')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider')
            ->toArray();
    }

    public function getModelsForProvider(string $provider): array
    {
        return AiModelPricing::query()
            ->where('provider', strtolower($provider))
            ->whereNotNull('model')
            ->where('active', true)
            ->orderBy('model')
            ->pluck('model')
            ->toArray();
    }
}
