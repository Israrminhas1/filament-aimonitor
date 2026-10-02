<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\AiMonitor\Filament\Concerns\HasAiMonitorNavigation;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Support\PricingUnit;
use Filament\AiMonitor\Support\Provider;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
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

                Components\Select::make('pricing_unit')
                    ->label('Pricing unit')
                    ->options(PricingUnit::options())
                    ->default(PricingUnit::TOKENS)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live()
                    ->helperText('Tokens for text and embedding models. Choose images, seconds or requests for models billed per generated image, per second of video or audio, or per request.'),

                Components\TextInput::make('input_per_1k')
                    ->label(fn (Get $get): string => 'Input / ' . PricingUnit::per($get('pricing_unit')) . ' (USD)')
                    ->numeric()
                    ->required()
                    ->step('0.000001')
                    ->minValue(0)
                    ->helperText(fn (Get $get): string => PricingUnit::isPerToken($get('pricing_unit'))
                        ? 'Provider price per 1M tokens ÷ 1000. e.g. $3.00 / 1M = 0.003'
                        : 'Provider price per ' . PricingUnit::per($get('pricing_unit')) . ', not per 1K. Enter 0 if only the output is billed.'),

                Components\TextInput::make('output_per_1k')
                    ->label(fn (Get $get): string => 'Output / ' . PricingUnit::per($get('pricing_unit')) . ' (USD)')
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
                    ->label('Input')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state, AiModelPricing $record) => static::formatPrice($state, $record))
                    ->description(fn (AiModelPricing $record) => static::describePrice($record->input_per_1k, $record)),
                Tables\Columns\TextColumn::make('output_per_1k')
                    ->label('Output')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state, AiModelPricing $record) => static::formatPrice($state, $record))
                    ->description(fn (AiModelPricing $record) => static::describePrice($record->output_per_1k, $record)),
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

    /**
     * "$0.002500 / 1K tokens" for token rows, "$0.040000 / image" for rows priced per unit.
     */
    protected static function formatPrice(float | int | null $price, AiModelPricing $record): string
    {
        return '$' . number_format((float) $price, 6) . ' / ' . PricingUnit::per($record->pricing_unit);
    }

    /**
     * The price per 1M tokens, which is how providers list it. Rows priced per unit have none.
     */
    protected static function describePrice(float | int | null $price, AiModelPricing $record): ?string
    {
        if (! PricingUnit::isPerToken($record->pricing_unit)) {
            return null;
        }

        return '$' . static::formatPerMillion($price) . ' / 1M';
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
