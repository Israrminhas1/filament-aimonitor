<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages;
use Filament\AiMonitor\Models\AiProviderApiKey;

class AiProviderApiKeyResource extends Resource
{
    protected static ?string $model = AiProviderApiKey::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-key';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'AI Monitor';
    }

    public static function getNavigationLabel(): string
    {
        return 'API Keys';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
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

                Components\TextInput::make('key_name')
                    ->label('Key Name')
                    ->maxLength(100)
                    ->helperText('Optional human-friendly label for this key')
                    ->placeholder('e.g. Production Key, Dev Key'),

                Components\TextInput::make('api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable(false)
                    ->required()
                    ->maxLength(255)
                    ->helperText('The API key will be encrypted in the database'),

                Components\TextInput::make('priority')
                    ->label('Priority')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1)
                    ->helperText('Lower number = higher priority (1 is highest)'),

                Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active keys can be used'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'openai' => 'success',
                        'anthropic' => 'info',
                        'gemini' => 'warning',
                        'perplexity' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('key_name')
                    ->label('Key Name')
                    ->searchable()
                    ->placeholder('(Unnamed)'),

                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Key')
                    ->formatStateUsing(fn () => '••••••••••••••••'),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 1 => 'success',
                        $state <= 3 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('active')->boolean()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiProviderApiKeys::route('/'),
            'create' => Pages\CreateAiProviderApiKey::route('/create'),
            'edit' => Pages\EditAiProviderApiKey::route('/{record}/edit'),
        ];
    }
}
