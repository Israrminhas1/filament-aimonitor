<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\AiMonitor\Models\AiRequest;

class AiRecentRequestsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Requests';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AiRequest::query()
                    ->with('user')
                    ->latest('occurred_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->description(fn (AiRequest $record) => ucfirst($record->provider))
                    ->limit(30),

                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->alignEnd()
                    ->numeric(),

                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Cost')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => $state !== null ? '$' . number_format($state, 4) : '--')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('System')
                    ->limit(15),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Time')
                    ->since(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
