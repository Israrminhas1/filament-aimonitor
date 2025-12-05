<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;
use Filament\AiMonitor\Models\AiRequest;

class AiRequestResource extends Resource
{
    protected static ?string $model = AiRequest::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'AI Monitor';
    }

    public static function getNavigationLabel(): string
    {
        return 'AI Requests';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'openai' => 'success',
                        'anthropic' => 'info',
                        'gemini' => 'warning',
                        'perplexity' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('request_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('prompt_tokens')
                    ->label('Prompt')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('completion_tokens')
                    ->label('Completion')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Cost (USD)')
                    ->formatStateUsing(fn ($state) => $state !== null ? '$' . number_format($state, 4) : '--')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'rate_limited' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Occurred At')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'gemini' => 'Gemini',
                        'perplexity' => 'Perplexity',
                    ])
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'rate_limited' => 'Rate Limited',
                    ])
                    ->multiple(),

                Filter::make('occurred_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('occurred_from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('occurred_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['occurred_from'], fn (Builder $query, $date) => $query->whereDate('occurred_at', '>=', $date))
                            ->when($data['occurred_until'], fn (Builder $query, $date) => $query->whereDate('occurred_at', '<=', $date));
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiRequests::route('/'),
            'view' => Pages\ViewAiRequest::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
