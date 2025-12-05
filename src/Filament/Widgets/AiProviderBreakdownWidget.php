<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\AiMonitor\Models\AiRequest;

class AiProviderBreakdownWidget extends ChartWidget
{
    protected ?string $heading = 'Cost by Provider';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return AiRequest::where('occurred_at', '>=', now()->subDays(30))
            ->whereNotNull('cost_usd')
            ->where('cost_usd', '>', 0)
            ->exists();
    }

    protected function getData(): array
    {
        $data = AiRequest::where('occurred_at', '>=', now()->subDays(30))
            ->select('provider', DB::raw('SUM(cost_usd) as cost'))
            ->groupBy('provider')
            ->orderByDesc('cost')
            ->get();

        $colors = [
            'openai' => '#10b981',
            'anthropic' => '#f59e0b',
            'gemini' => '#3b82f6',
            'google' => '#3b82f6',
            'perplexity' => '#8b5cf6',
            'cohere' => '#ec4899',
            'mistral' => '#14b8a6',
        ];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('cost')->map(fn ($v) => round($v ?? 0, 2))->toArray(),
                    'backgroundColor' => $data->map(fn ($row) => $colors[strtolower($row->provider)] ?? '#6b7280')->toArray(),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->pluck('provider')->map(fn ($v) => ucfirst($v))->toArray(),
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
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
