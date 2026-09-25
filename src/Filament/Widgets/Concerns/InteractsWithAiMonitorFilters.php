<?php

namespace Filament\AiMonitor\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Filament\AiMonitor\Models\AiRequest;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reads the AI Monitor dashboard filters (period + provider). Widgets fall back to
 * the last 30 days across all providers when placed on a page without those filters.
 */
trait InteractsWithAiMonitorFilters
{
    use InteractsWithPageFilters;

    /**
     * @return array<int, string>
     */
    public static function getPeriodOptions(): array
    {
        return [
            7 => 'Last 7 days',
            30 => 'Last 30 days',
            90 => 'Last 90 days',
            365 => 'Last 12 months',
        ];
    }

    protected function getPeriodDays(): int
    {
        $days = (int) ($this->pageFilters['period'] ?? 30);

        return array_key_exists($days, static::getPeriodOptions()) ? $days : 30;
    }

    protected function getPeriodStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays($this->getPeriodDays() - 1)->startOfDay();
    }

    protected function getPreviousPeriodStart(): CarbonImmutable
    {
        return $this->getPeriodStart()->subDays($this->getPeriodDays());
    }

    protected function getProviderFilter(): ?string
    {
        $provider = $this->pageFilters['provider'] ?? null;

        return filled($provider) ? (string) $provider : null;
    }

    protected function getPeriodLabel(): string
    {
        return static::getPeriodOptions()[$this->getPeriodDays()];
    }

    /**
     * Requests in the selected period (and provider, if any).
     */
    protected function filteredRequests(): Builder
    {
        return $this->applyProviderFilter(
            AiRequest::query()->occurredBetween($this->getPeriodStart())
        );
    }

    protected function applyProviderFilter(Builder $query): Builder
    {
        return $query->when(
            $this->getProviderFilter(),
            fn (Builder $query, string $provider) => $query->where($query->getModel()->qualifyColumn('provider'), $provider),
        );
    }
}
