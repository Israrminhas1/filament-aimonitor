<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\AiMonitor\Filament\Concerns\HasAiMonitorNavigation;
use Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Services\AiPricingService;
use Filament\AiMonitor\Support\Provider;
use Filament\AiMonitor\Support\Status;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AiRequestResource extends Resource
{
    use HasAiMonitorNavigation;

    protected static ?string $model = AiRequest::class;

    /**
     * Records are scoped by the plugin's own tenant scope, not Filament's tenant ownership relationship.
     */
    protected static bool $isScopedToTenant = false;

    protected static int $aiMonitorNavigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cpu-chip';
    }

    public static function getNavigationLabel(): string
    {
        return 'AI Requests';
    }

    public static function getModelLabel(): string
    {
        return 'AI request';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $userTitle = config('ai-monitor.user_title_attribute', 'name');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Provider::label($state))
                    ->color(fn (?string $state): string => Provider::color($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('request_type')
                    ->label('Type')
                    ->badge()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('prompt_tokens')
                    ->label('Prompt')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completion_tokens')
                    ->label('Completion')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_tokens')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->summarize(Sum::make()->label('Total tokens')->numeric()),

                Tables\Columns\TextColumn::make('cost_usd')
                    ->label('Cost (USD)')
                    ->formatStateUsing(fn ($state) => static::formatCost($state))
                    ->placeholder('Missing')
                    ->sortable()
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total cost')->formatStateUsing(fn ($state) => static::formatCost($state ?? 0))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Status::label($state))
                    ->color(fn (?string $state): string => Status::color($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("user.{$userTitle}")
                    ->label('User')
                    ->placeholder('System')
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
                    ->options(fn () => Provider::options(
                        AiRequest::query()->distinct()->pluck('provider')->all()
                    ))
                    ->multiple(),

                SelectFilter::make('status')
                    ->options(Status::options())
                    ->multiple(),

                TernaryFilter::make('cost_missing')
                    ->label('Pricing')
                    ->placeholder('All requests')
                    ->trueLabel('Missing cost only')
                    ->falseLabel('Priced only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('cost_usd'),
                        false: fn (Builder $query) => $query->whereNotNull('cost_usd'),
                    ),

                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('occurred_from')->label('From'),
                        DatePicker::make('occurred_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['occurred_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('occurred_at', '>=', $date))
                            ->when($data['occurred_until'] ?? null, fn (Builder $query, $date) => $query->whereDate('occurred_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['occurred_from'] ?? null) {
                            $indicators[] = 'From ' . $data['occurred_from'];
                        }

                        if ($data['occurred_until'] ?? null) {
                            $indicators[] = 'Until ' . $data['occurred_until'];
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('recalculateCost')
                    ->label('Recalculate cost')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->modalDescription('Recalculate the cost of the selected requests using the current pricing table. Costs that were logged manually are left unchanged.')
                    ->action(function (Collection $records): void {
                        $pricing = app(AiPricingService::class);
                        $updated = $records->filter(fn (AiRequest $record) => $pricing->recalculate($record))->count();

                        Notification::make()
                            ->title("Recalculated {$updated} of {$records->count()} requests")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function formatCost(mixed $state): ?string
    {
        return $state === null ? null : '$' . number_format((float) $state, 4);
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
