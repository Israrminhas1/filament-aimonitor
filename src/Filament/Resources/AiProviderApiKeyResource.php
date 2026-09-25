<?php

namespace Filament\AiMonitor\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\AiMonitor\Filament\Concerns\HasAiMonitorNavigation;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Support\Provider;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

class AiProviderApiKeyResource extends Resource
{
    use HasAiMonitorNavigation;

    protected static ?string $model = AiProviderApiKey::class;

    protected static bool $isScopedToTenant = false;

    protected static int $aiMonitorNavigationSort = 4;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-key';
    }

    public static function getNavigationLabel(): string
    {
        return 'API Keys';
    }

    public static function getModelLabel(): string
    {
        return 'API key';
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

                Components\TextInput::make('key_name')
                    ->label('Key Name')
                    ->maxLength(100)
                    ->helperText('Optional human-friendly label for this key')
                    ->placeholder('e.g. Production Key, Dev Key'),

                // The stored key is never sent back to the browser. On edit, leave the
                // field empty to keep the current key.
                Components\TextInput::make('api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->formatStateUsing(fn () => null)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(1000)
                    ->placeholder(fn (string $operation): ?string => $operation === 'edit' ? 'Leave empty to keep the current key' : null)
                    ->helperText('The API key is encrypted in the database.'),

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
                    ->formatStateUsing(fn (?string $state) => Provider::label($state))
                    ->color(fn (?string $state): string => Provider::color($state)),

                Tables\Columns\TextColumn::make('key_name')
                    ->label('Key Name')
                    ->searchable()
                    ->placeholder('(Unnamed)'),

                Tables\Columns\TextColumn::make('masked_key')
                    ->label('API Key')
                    ->fontFamily('mono')
                    ->getStateUsing(fn (AiProviderApiKey $record) => static::maskKey($record)),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 1 => 'success',
                        $state <= 3 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('active')->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ->defaultSort('priority');
    }

    /**
     * Show only the last four characters of a key, e.g. "••••••••3xYz".
     */
    public static function maskKey(AiProviderApiKey $record): string
    {
        try {
            $key = (string) $record->api_key;
        } catch (Throwable) {
            return 'Unable to decrypt';
        }

        return str_repeat('•', 8) . (strlen($key) > 8 ? substr($key, -4) : '');
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
