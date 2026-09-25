<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\AiMonitor\Filament\Concerns\HasAiMonitorNavigation;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Support\Provider;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AiModelPricingResource extends Resource
{
    use HasAiMonitorNavigation;

    protected static ?string $model = AiModelPricing::class;

    protected static bool $isScopedToTenant = false;

    protected static int $aiMonitorNavigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Model Pricing';
    }

    public static function getModelLabel(): string
    {
        return 'model price';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('provider')
                    ->label('Provider')
                    ->required()
                    ->maxLength(50)
                    ->datalist(array_keys(Provider::LABELS))
                    ->helperText('e.g. openai, anthropic, gemini, perplexity')
                    ->dehydrateStateUsing(fn (?string $state) => $state === null ? null : strtolower(trim($state))),

                Components\TextInput::make('model')
                    ->label('Model')
                    ->maxLength(100)
                    ->helperText('Matches exact model names and prefixes: "gpt-4o" also prices "gpt-4o-2024-08-06". Leave empty for a provider default or global fallback.'),

                Components\TextInput::make('input_per_1k')
                    ->label('Input / 1K tokens (USD)')
                    ->numeric()
                    ->required()
                    ->step('0.000001')
                    ->minValue(0)
                    ->helperText('Provider price per 1M tokens ÷ 1000. e.g. $3.00 / 1M = 0.003'),

                Components\TextInput::make('output_per_1k')
                    ->label('Output / 1K tokens (USD)')
                    ->numeric()
                    ->required()
                    ->step('0.000001')
                    ->minValue(0),

                Components\Toggle::make('is_default')
                    ->label('Provider default')
                    ->helperText('Used when no model row is found for this provider.'),

                Components\Toggle::make('is_fallback')
                    ->label('Global fallback')
                    ->helperText('Used when no provider/model row is found at all.'),

                Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Provider::label($state))
                    ->color(fn (?string $state): string => Provider::color($state))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->sortable()
                    ->searchable()
                    ->placeholder('* (default or fallback)'),
                Tables\Columns\TextColumn::make('input_per_1k')
                    ->label('Input / 1K')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => '$' . number_format((float) $state, 6))
                    ->description(fn (AiModelPricing $record) => '$' . static::formatPerMillion($record->input_per_1k) . ' / 1M'),
                Tables\Columns\TextColumn::make('output_per_1k')
                    ->label('Output / 1K')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => '$' . number_format((float) $state, 6))
                    ->description(fn (AiModelPricing $record) => '$' . static::formatPerMillion($record->output_per_1k) . ' / 1M'),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->getStateUsing(fn (AiModelPricing $record) => $record->is_fallback ? 'Fallback' : ($record->is_default ? 'Default' : 'Model'))
                    ->color(fn (string $state): string => match ($state) {
                        'Model' => 'success',
                        'Default' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('active')->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options(fn () => Provider::options(AiModelPricing::query()->distinct()->pluck('provider')->all())),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('provider');
    }

    protected static function formatPerMillion(float | int | null $perThousand): string
    {
        return rtrim(rtrim(number_format(((float) $perThousand) * 1000, 4), '0'), '.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiModelPricings::route('/'),
            'create' => Pages\CreateAiModelPricing::route('/create'),
            'edit' => Pages\EditAiModelPricing::route('/{record}/edit'),
        ];
    }
}
