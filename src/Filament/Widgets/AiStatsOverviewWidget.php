<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Status;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AiStatsOverviewWidget extends BaseWidget
{
    use InteractsWithAiMonitorFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $current = $this->aggregate($this->filteredRequests());
        $previous = $this->aggregate($this->applyProviderFilter(
            AiRequest::query()->occurredBetween($this->getPreviousPeriodStart(), $this->getPeriodStart())
        ));

        $successRate = $current['requests'] > 0 ? round(($current['successes'] / $current['requests']) * 100, 1) : 0;
        $requestsTrend = $this->trend($current['requests'], $previous['requests']);
        $costTrend = $this->trend($current['cost'], $previous['cost']);
        $avgCost = $current['requests'] > 0 ? $current['cost'] / $current['requests'] : 0;

        $daily = $this->dailyTotals();

        return [
            Stat::make('Total Requests', number_format($current['requests']))
                ->description($this->trendDescription($requestsTrend))
                ->descriptionIcon($requestsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($requestsTrend >= 0 ? 'success' : 'danger')
                ->chart($daily['requests']),

            Stat::make('Total Cost', '$' . number_format($current['cost'], 2))
                ->description($this->trendDescription($costTrend))
                ->descriptionIcon($costTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                // Rising spend is highlighted as a warning, falling spend as good news.
                ->color($costTrend > 0 ? 'danger' : 'success')
                ->chart($daily['cost']),

            Stat::make('Tokens Used', $this->formatTokens($current['tokens']))
                ->description('Avg $' . number_format($avgCost, 4) . ' per request')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Success Rate', $successRate . '%')
                ->description(number_format($current['successes']) . ' successful of ' . number_format($current['requests']))
                ->descriptionIcon($successRate >= 95 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($current['requests'] === 0 ? 'gray' : ($successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger'))),
        ];
    }

    /**
     * @return array{requests: int, cost: float, tokens: int, successes: int}
     */
    protected function aggregate(Builder $query): array
    {
        $row = $query
            ->toBase()
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as tokens')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as successes', [Status::SUCCESS])
            ->first();

        return [
            'requests' => (int) ($row->requests ?? 0),
            'cost' => (float) ($row->cost ?? 0),
            'tokens' => (int) ($row->tokens ?? 0),
            'successes' => (int) ($row->successes ?? 0),
        ];
    }

    /**
     * Last 7 days of requests and cost for the sparklines, in a single query.
     *
     * @return array{requests: array<int>, cost: array<float>}
     */
    protected function dailyTotals(): array
    {
        $start = now()->subDays(6)->startOfDay();
        $date = AiRequest::dateExpression();

        $rows = $this->applyProviderFilter(AiRequest::query()->occurredBetween($start))
            ->toBase()
            ->select(DB::raw("{$date} as day"))
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->groupBy(DB::raw($date))
            ->get()
            ->keyBy(fn ($row) => substr((string) $row->day, 0, 10));

        $requests = [];
        $cost = [];

        for ($i = 6; $i >= 0; $i--) {
            $row = $rows->get(now()->subDays($i)->toDateString());
            $requests[] = (int) ($row->requests ?? 0);
            $cost[] = (float) ($row->cost ?? 0);
        }

        return ['requests' => $requests, 'cost' => $cost];
    }

    protected function trend(float | int $current, float | int $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100 : 0;
    }

    protected function trendDescription(float $trend): string
    {
        return ($trend >= 0 ? '+' : '') . $trend . '% vs previous ' . $this->getPeriodDays() . ' days';
    }

    protected function formatTokens(int $tokens): string
    {
        return match (true) {
            $tokens >= 1_000_000_000 => number_format($tokens / 1_000_000_000, 1) . 'B',
            $tokens >= 1_000_000 => number_format($tokens / 1_000_000, 1) . 'M',
            $tokens >= 1_000 => number_format($tokens / 1_000, 1) . 'K',
            default => number_format($tokens),
        };
    }
}
