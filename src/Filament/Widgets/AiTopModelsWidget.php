<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\AiMonitor\Models\AiRequest;

class AiTopModelsWidget extends Widget
{
    protected string $view = 'ai-monitor::filament.widgets.ai-top-models-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return AiRequest::where('occurred_at', '>=', now()->subDays(30))->exists();
    }

    public function getModels(): array
    {
        return AiRequest::query()
            ->where('occurred_at', '>=', now()->subDays(30))
            ->select(
                'provider',
                'model',
                DB::raw('COUNT(*) as requests_count'),
                DB::raw('SUM(cost_usd) as total_cost'),
                DB::raw('SUM(total_tokens) as tokens_sum')
            )
            ->groupBy('provider', 'model')
            ->orderByDesc('total_cost')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
