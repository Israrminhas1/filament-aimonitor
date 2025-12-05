<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages;
use Filament\AiMonitor\Models\AiModelPricing;

class AiModelPricingResource extends Resource
{
    protected static ?string $model = AiModelPricing::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'AI Monitor';
    }

    public static function getNavigationLabel(): string
    {
        return 'Model Pricing';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Components\TextInput::make('provider')
                    ->label('Provider')
                    ->required()
                    ->maxLength(50)
                    ->helperText('e.g. openai, anthropic, gemini, perplexity')
                    ->afterStateUpdated(fn ($state, callable $set) => $set('provider', strtolower($state)))
                    ->live(onBlur: true),

                Components\TextInput::make('model')
                    ->label('Model')
                    ->maxLength(100)
                    ->helperText('e.g. gpt-4o, claude-3-opus, leave empty for provider default'),

                Components\TextInput::make('input_per_1k')
                    ->label('Input / 1K tokens (USD)')
                    ->numeric()
                    ->required()
                    ->step('0.000001')
                    ->minValue(0),

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
                Tables\Columns\TextColumn::make('provider')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('model')->label('Model')->sortable()->searchable()->placeholder('* (default or fallback)'),
                Tables\Columns\TextColumn::make('input_per_1k')->label('Input / 1K')->numeric()->alignEnd()->formatStateUsing(fn ($state) => number_format($state, 6)),
                Tables\Columns\TextColumn::make('output_per_1k')->label('Output / 1K')->numeric()->alignEnd()->formatStateUsing(fn ($state) => number_format($state, 6)),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->is_fallback ? 'Fallback' : ($record->is_default ? 'Default' : 'Model'))
                    ->color(fn (string $state): string => match ($state) {
                        'Model' => 'success',
                        'Default' => 'warning',
                        'Fallback' => 'gray',
                    }),
                Tables\Columns\IconColumn::make('active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Active')->placeholder('All')->trueLabel('Active only')->falseLabel('Inactive only'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('provider');
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
