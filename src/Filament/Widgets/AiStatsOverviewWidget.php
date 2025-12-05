<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\AiMonitor\Models\AiRequest;

class AiStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $start = now()->subDays(30)->startOfDay();
        $prevStart = now()->subDays(60)->startOfDay();
        $prevEnd = $start->copy();

        $totalRequests = AiRequest::where('occurred_at', '>=', $start)->count();
        $totalCost = AiRequest::where('occurred_at', '>=', $start)->sum('cost_usd') ?? 0;
        $totalTokens = AiRequest::where('occurred_at', '>=', $start)->sum('total_tokens') ?? 0;
        $successCount = AiRequest::where('occurred_at', '>=', $start)->where('status', 'success')->count();
        $successRate = $totalRequests > 0 ? round(($successCount / $totalRequests) * 100, 1) : 0;

        $prevRequests = AiRequest::whereBetween('occurred_at', [$prevStart, $prevEnd])->count();
        $prevCost = AiRequest::whereBetween('occurred_at', [$prevStart, $prevEnd])->sum('cost_usd') ?? 0;

        $requestsTrend = $prevRequests > 0 ? round((($totalRequests - $prevRequests) / $prevRequests) * 100, 1) : ($totalRequests > 0 ? 100 : 0);
        $costTrend = $prevCost > 0 ? round((($totalCost - $prevCost) / $prevCost) * 100, 1) : ($totalCost > 0 ? 100 : 0);

        $tokensFormatted = $totalTokens >= 1000000
            ? number_format($totalTokens / 1000000, 1) . 'M'
            : ($totalTokens >= 1000 ? number_format($totalTokens / 1000, 1) . 'K' : number_format($totalTokens));

        return [
            Stat::make('Total Requests', number_format($totalRequests))
                ->description($requestsTrend >= 0 ? "+{$requestsTrend}% from last period" : "{$requestsTrend}% from last period")
                ->descriptionIcon($requestsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($requestsTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getRequestsChart()),

            Stat::make('Total Cost', '$' . number_format($totalCost, 2))
                ->description($costTrend >= 0 ? "+{$costTrend}% from last period" : "{$costTrend}% from last period")
                ->descriptionIcon($costTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($costTrend > 0 ? 'danger' : 'success')
                ->chart($this->getCostChart()),

            Stat::make('Tokens Used', $tokensFormatted)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Success Rate', $successRate . '%')
                ->description($successCount . ' successful of ' . $totalRequests)
                ->descriptionIcon($successRate >= 95 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger')),
        ];
    }

    protected function getRequestsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = AiRequest::whereDate('occurred_at', now()->subDays($i))->count();
        }
        return $data;
    }

    protected function getCostChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = (float) AiRequest::whereDate('occurred_at', now()->subDays($i))->sum('cost_usd');
        }
        return $data;
    }
}
