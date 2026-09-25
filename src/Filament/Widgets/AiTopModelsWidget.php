<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Provider;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AiTopModelsWidget extends BaseWidget
{
    use InteractsWithAiMonitorFilters;

    protected static ?string $heading = 'Top Models by Cost';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // The inner query carries the tenant scope; the outer query reads from the
                // aggregated subquery, which has no tenant_id column, so it must not be scoped again.
                $aggregated = $this->filteredRequests()
                    ->select(
                        DB::raw('MIN(id) as id'),
                        'provider',
                        'model',
                        DB::raw('COUNT(*) as requests_count'),
                        DB::raw('COALESCE(SUM(cost_usd), 0) as total_cost'),
                        DB::raw('COALESCE(SUM(total_tokens), 0) as tokens_sum'),
                    )
                    ->groupBy('provider', 'model')
                    ->orderByDesc('total_cost')
                    ->limit(5);

                return AiRequest::query()
                    ->withoutGlobalScopes()
                    ->fromSub($aggregated, 'ai_requests');
            })
            ->defaultSort('total_cost', 'desc')
            ->emptyStateHeading('No requests in this period')
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->description(fn ($record) => Provider::label($record->provider)),
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
