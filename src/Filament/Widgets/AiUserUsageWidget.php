<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Filament\AiMonitor\Models\AiRequest;

class AiUserUsageWidget extends BaseWidget
{
    protected static ?string $heading = 'Usage by User';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return AiRequest::where('occurred_at', '>=', now()->subDays(30))->exists();
    }

    public function table(Table $table): Table
    {
        $userModel = config('ai-monitor.user_model', 'App\\Models\\User');
        $usersTable = (new $userModel)->getTable();

        return $table
            ->query(
                AiRequest::query()
                    ->fromSub(
                        AiRequest::query()
                            ->where('ai_requests.occurred_at', '>=', now()->subDays(30))
                            ->leftJoin($usersTable, 'ai_requests.user_id', '=', "{$usersTable}.id")
                            ->select(
                                'ai_requests.user_id as id',
                                DB::raw("COALESCE({$usersTable}.name, 'System') as user_name"),
                                DB::raw('COUNT(*) as requests_count'),
                                DB::raw('SUM(ai_requests.cost_usd) as total_cost')
                            )
                            ->groupBy('ai_requests.user_id', "{$usersTable}.name")
                            ->orderByDesc('total_cost')
                            ->limit(5),
                        'ai_requests'
                    )
            )
            ->defaultSort('total_cost', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User'),
                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('usd')
                    ->alignEnd(),
            ])
            ->paginated(false);
    }
}
