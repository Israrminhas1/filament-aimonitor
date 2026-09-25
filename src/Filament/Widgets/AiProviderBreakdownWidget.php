<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Support\Provider;
use Filament\Widgets\ChartWidget;

class AiProviderBreakdownWidget extends ChartWidget
{
    use InteractsWithAiMonitorFilters;

    protected ?string $heading = 'Cost by Provider';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    public function getDescription(): ?string
    {
        return $this->getPeriodLabel();
    }

    protected function getData(): array
    {
        $data = $this->filteredRequests()
            ->toBase()
            ->select('provider')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->groupBy('provider')
            ->havingRaw('SUM(cost_usd) > 0')
            ->orderByDesc('cost')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->map(fn ($row) => round((float) $row->cost, 2))->all(),
                    'backgroundColor' => $data->map(fn ($row) => Provider::chartColor($row->provider))->all(),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->map(fn ($row) => Provider::label($row->provider))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '60%',
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
