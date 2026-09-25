<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Actions\Action;
use Filament\AiMonitor\AiMonitorPlugin;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Provider;
use Filament\AiMonitor\Support\Status;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AiRecentRequestsWidget extends BaseWidget
{
    use InteractsWithAiMonitorFilters;

    protected static ?string $heading = 'Recent Requests';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        $userTitle = config('ai-monitor.user_title_attribute', 'name');
        $viewUrl = fn (AiRequest $record): ?string => AiMonitorPlugin::resourceUrl(AiRequestResource::class, 'view', ['record' => $record]);

        return $table
            ->query(fn () => $this->applyProviderFilter(AiRequest::query())
                ->with('user')
                ->latest('occurred_at'))
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->description(fn (AiRequest $record) => Provider::label($record->provider))
                    ->limit(30),

                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->alignEnd()
                    ->numeric(),

                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Cost')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => AiRequestResource::formatCost($state))
                    ->placeholder('Missing')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Status::label($state))
                    ->color(fn (?string $state): string => Status::color($state)),

                Tables\Columns\TextColumn::make("user.{$userTitle}")
                    ->label('User')
                    ->placeholder('System')
                    ->limit(20),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Time')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->recordUrl($viewUrl)
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->link()
                    ->url(fn () => AiMonitorPlugin::resourceUrl(AiRequestResource::class))
                    ->visible(fn () => AiMonitorPlugin::resourceUrl(AiRequestResource::class) !== null),
            ])
            ->emptyStateHeading('No requests logged yet')
            ->emptyStateDescription('Log requests with ai_log([...]) and they will appear here.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
