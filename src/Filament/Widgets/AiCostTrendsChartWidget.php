<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AiCostTrendsChartWidget extends ChartWidget
{
    use InteractsWithAiMonitorFilters;

    protected ?string $heading = 'Cost & Request Trends';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '60s';

    public function getDescription(): ?string
    {
        return $this->getPeriodLabel();
    }

    protected function getData(): array
    {
        $date = AiRequest::dateExpression();

        $rows = $this->filteredRequests()
            ->toBase()
            ->select(DB::raw("{$date} as day"))
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost')
            ->groupBy(DB::raw($date))
            ->get()
            ->keyBy(fn ($row) => substr((string) $row->day, 0, 10));

        $labels = [];
        $costData = [];
        $requestData = [];

        $start = $this->getPeriodStart();
        $days = $this->getPeriodDays();

        for ($i = 0; $i < $days; $i++) {
            /** @var CarbonImmutable $day */
            $day = $start->addDays($i);
            $row = $rows->get($day->toDateString());

            $labels[] = $day->format($days > 90 ? 'M d, Y' : 'M d');
            $costData[] = round((float) ($row->cost ?? 0), 4);
            $requestData[] = (int) ($row->requests ?? 0);
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
                    'pointRadius' => $days > 90 ? 0 : 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Requests',
                    'data' => $requestData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => $days > 90 ? 0 : 2,
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
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => [
                'y' => ['type' => 'linear', 'display' => true, 'position' => 'left', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Cost ($)']],
                'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Requests']],
            ],
        ];
    }
}
