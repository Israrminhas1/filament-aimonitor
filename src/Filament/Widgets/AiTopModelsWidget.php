<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Filament\AiMonitor\Models\AiRequest;

class AiTopModelsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Models by Cost';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return AiRequest::where('occurred_at', '>=', now()->subDays(30))->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AiRequest::query()
                    ->fromSub(
                        AiRequest::query()
                            ->where('occurred_at', '>=', now()->subDays(30))
                            ->select(
                                DB::raw('MIN(id) as id'),
                                'provider',
                                'model',
                                DB::raw('COUNT(*) as requests_count'),
                                DB::raw('SUM(cost_usd) as total_cost'),
                                DB::raw('SUM(total_tokens) as tokens_sum')
                            )
                            ->groupBy('provider', 'model')
                            ->orderByDesc('total_cost')
                            ->limit(5),
                        'ai_requests'
                    )
            )
            ->defaultSort('total_cost', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->description(fn ($record) => ucfirst($record->provider)),
                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
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
