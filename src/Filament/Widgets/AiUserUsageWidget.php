<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AiUserUsageWidget extends BaseWidget
{
    use InteractsWithAiMonitorFilters;

    protected static ?string $heading = 'Usage by User';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $userModel = config('ai-monitor.user_model', 'App\\Models\\User');
                $user = new $userModel;
                $usersTable = $user->getTable();
                $userKey = $user->getKeyName();
                $userTitle = config('ai-monitor.user_title_attribute', 'name');

                $aggregated = $this->filteredRequests()
                    ->leftJoin($usersTable, 'ai_requests.user_id', '=', "{$usersTable}.{$userKey}")
                    ->select(
                        // Requests without a user are grouped under "System" with key 0,
                        // since table records need a non-null key.
                        DB::raw('COALESCE(ai_requests.user_id, 0) as id'),
                        DB::raw("COALESCE({$usersTable}.{$userTitle}, 'System') as user_name"),
                        DB::raw('COUNT(*) as requests_count'),
                        DB::raw('COALESCE(SUM(ai_requests.total_tokens), 0) as tokens_sum'),
                        DB::raw('COALESCE(SUM(ai_requests.cost_usd), 0) as total_cost'),
                    )
                    ->groupBy('ai_requests.user_id', "{$usersTable}.{$userTitle}")
                    ->orderByDesc('total_cost')
                    ->limit(5);

                return AiRequest::query()
                    ->withoutGlobalScopes()
                    ->fromSub($aggregated, 'ai_requests');
            })
            ->defaultSort('total_cost', 'desc')
            ->emptyStateHeading('No requests in this period')
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User'),
                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
                    ->numeric()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('tokens_sum')
                    ->label('Tokens')
                    ->numeric()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('usd')
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
