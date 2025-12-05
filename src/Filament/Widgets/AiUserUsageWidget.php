<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\AiMonitor\Models\AiRequest;

class AiUserUsageWidget extends Widget
{
    protected string $view = 'ai-monitor::filament.widgets.ai-user-usage-widget';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return AiRequest::where('ai_requests.occurred_at', '>=', now()->subDays(30))->exists();
    }

    public function getUsers(): array
    {
        return AiRequest::query()
            ->where('ai_requests.occurred_at', '>=', now()->subDays(30))
            ->leftJoin('users', 'ai_requests.user_id', '=', 'users.id')
            ->select(
                'ai_requests.user_id',
                DB::raw('COALESCE(users.name, \'System\') as user_name'),
                DB::raw('COUNT(*) as requests_count'),
                DB::raw('SUM(ai_requests.cost_usd) as total_cost')
            )
            ->groupBy('ai_requests.user_id', 'users.name')
            ->orderByDesc('total_cost')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
