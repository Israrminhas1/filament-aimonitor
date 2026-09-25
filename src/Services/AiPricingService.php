<?php

namespace Filament\AiMonitor\Services;

use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Tenancy;
use Illuminate\Support\Collection;

class AiPricingService
{
    /**
     * Active pricing rows, memoized per tenant for the lifetime of the service.
     *
     * @var array<string, Collection<int, AiModelPricing>>
     */
    protected array $cache = [];

    /**
     * @var array<string, int>
     */
    protected array $cachedAt = [];

    /**
     * Seconds before memoized pricing is reloaded, so long-running processes
     * (queue workers, Octane) pick up pricing edits made elsewhere.
     */
    protected int $ttl = 60;

    public function getPricing(string $provider, ?string $model = null): ?array
    {
        $row = $this->resolvePricingRow(strtolower($provider), $model);

        if (! $row) {
            return null;
        }

        return [
            'input_per_1k' => $row->input_per_1k,
            'output_per_1k' => $row->output_per_1k,
        ];
    }

    public function hasPricing(string $provider, ?string $model = null): bool
    {
        return $this->getPricing($provider, $model) !== null;
    }

    public function hasAnyPricing(): bool
    {
        return $this->activePricing()->isNotEmpty();
    }

    /**
     * Resolution order:
     *  1. Exact model match (case-insensitive).
     *  2. A dated or tagged snapshot of a priced model, so `gpt-4o-2024-08-06` uses the
     *     `gpt-4o` row and `claude-3-5-sonnet-latest` uses `claude-3-5-sonnet`. A different
     *     model that merely shares a prefix (`gpt-4o-mini`) does not match `gpt-4o`.
     *  3. The provider's default row.
     *  4. The global fallback row.
     */
    protected function resolvePricingRow(string $provider, ?string $model): ?AiModelPricing
    {
        $rows = $this->activePricing();

        if (filled($model)) {
            $model = strtolower($model);

            $modelRows = $rows->filter(fn (AiModelPricing $row) => strtolower($row->provider) === $provider
                && filled($row->model)
                && ! $row->is_default
                && ! $row->is_fallback);

            $exact = $modelRows->first(fn (AiModelPricing $row) => strtolower($row->model) === $model);

            if ($exact) {
                return $exact;
            }

            $prefix = $modelRows
                ->filter(fn (AiModelPricing $row) => $this->isVersionOf($model, strtolower($row->model)))
                ->sortByDesc(fn (AiModelPricing $row) => strlen($row->model))
                ->first();

            if ($prefix) {
                return $prefix;
            }
        }

        return $rows->first(fn (AiModelPricing $row) => strtolower($row->provider) === $provider && $row->is_default)
            ?? $rows->first(fn (AiModelPricing $row) => $row->is_fallback);
    }

    /**
     * Whether `$model` is `$base` followed by a snapshot suffix: a date (`-20241022`,
     * `-2024-08-06`, `-0613`), `-latest`, `-preview…`, `-exp…`, or a Vertex-style `@…`.
     */
    protected function isVersionOf(string $model, string $base): bool
    {
        if (! str_starts_with($model, $base)) {
            return false;
        }

        return (bool) preg_match('/^(-\d{4}|-latest|-preview|-exp|@)/', substr($model, strlen($base)));
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

        $inputCost = (($promptTokens ?? 0) / 1000) * $pricing['input_per_1k'];
        $outputCost = (($completionTokens ?? 0) / 1000) * $pricing['output_per_1k'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Recalculate the cost of a logged request from the current pricing table.
     * Requests whose cost was supplied manually are left untouched unless `$force` is true.
     */
    public function recalculate(AiRequest $request, bool $force = false): bool
    {
        $meta = $request->meta ?? [];

        if (! $force && ($meta['cost_source'] ?? null) === 'manual') {
            return false;
        }

        $cost = $this->calculateCost(
            $request->provider,
            $request->model,
            $request->prompt_tokens,
            $request->completion_tokens,
        );

        if ($cost === null) {
            return false;
        }

        unset($meta['pricing_missing']);
        $meta['cost_source'] = 'auto';

        $request->forceFill(['cost_usd' => $cost, 'meta' => $meta])->save();

        return true;
    }

    public function getProviders(): array
    {
        return $this->activePricing()
            ->pluck('provider')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getModelsForProvider(string $provider): array
    {
        $provider = strtolower($provider);

        return $this->activePricing()
            ->filter(fn (AiModelPricing $row) => $row->provider === $provider && filled($row->model))
            ->pluck('model')
            ->sort()
            ->values()
            ->all();
    }

    public function flushCache(): void
    {
        $this->cache = [];
        $this->cachedAt = [];
    }

    /**
     * @return Collection<int, AiModelPricing>
     */
    protected function activePricing(): Collection
    {
        $key = (string) (Tenancy::currentId() ?? '__global');

        if (! isset($this->cache[$key]) || (time() - $this->cachedAt[$key]) >= $this->ttl) {
            $this->cache[$key] = AiModelPricing::query()->where('active', true)->get();
            $this->cachedAt[$key] = time();
        }

        return $this->cache[$key];
    }
}
