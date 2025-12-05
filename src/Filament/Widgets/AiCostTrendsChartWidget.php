<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\AiMonitor\Models\AiRequest;

class AiCostTrendsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Cost & Request Trends';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '400px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $days = 30;
        $labels = [];
        $costData = [];
        $requestData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $dayQuery = AiRequest::whereDate('occurred_at', $date);
            $costData[] = round((clone $dayQuery)->sum('cost_usd') ?? 0, 4);
            $requestData[] = (clone $dayQuery)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cost ($)',
                    'data' => $costData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Requests',
                    'data' => $requestData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['type' => 'linear', 'display' => true, 'position' => 'left', 'beginAtZero' => true],
                'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }
}
