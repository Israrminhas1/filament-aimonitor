<?php

namespace Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;

use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\AiMonitor\Support\Provider;
use Filament\AiMonitor\Support\Status;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class ViewAiRequest extends ViewRecord
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        $userTitle = config('ai-monitor.user_title_attribute', 'name');

        return $schema
            ->columns(1)
            ->components([
                Section::make('Request Information')
                    ->schema([
                        TextEntry::make('provider')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => Provider::label($state))
                            ->color(fn (?string $state): string => Provider::color($state)),
                        TextEntry::make('model'),
                        TextEntry::make('request_type')->label('Request Type')->badge()->placeholder('—'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => Status::label($state))
                            ->color(fn (?string $state): string => Status::color($state)),
                        TextEntry::make('http_code')->label('HTTP Code')->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make('Token Usage')
                    ->schema([
                        TextEntry::make('prompt_tokens')->label('Prompt Tokens')->numeric()->placeholder('—'),
                        TextEntry::make('completion_tokens')->label('Completion Tokens')->numeric()->placeholder('—'),
                        TextEntry::make('total_tokens')->label('Total Tokens')->numeric()->weight(FontWeight::Bold),
                    ])
                    ->columns(3),

                Section::make('Cost')
                    ->schema([
                        TextEntry::make('cost_usd')
                            ->label('Cost (USD)')
                            ->formatStateUsing(fn ($state) => AiRequestResource::formatCost($state))
                            ->placeholder('Missing — no matching pricing')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('meta.cost_source')
                            ->label('Cost Source')
                            ->default('auto')
                            ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'auto'))
                            ->badge()
                            ->color(fn (?string $state) => match ($state ?? 'auto') {
                                'auto' => 'success',
                                'manual' => 'warning',
                                'missing' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Section::make('Relations')
                    ->schema([
                        TextEntry::make("user.{$userTitle}")->label('User')->placeholder('System'),
                        TextEntry::make('tenant_id')->label('Tenant ID')->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Timeline')
                    ->schema([
                        TextEntry::make('occurred_at')->label('Occurred At')->dateTime('M d, Y H:i:s'),
                        TextEntry::make('created_at')->label('Logged At')->dateTime('M d, Y H:i:s'),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('meta')
                            ->hiddenLabel()
                            ->state(fn ($record) => json_encode($record->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                            ->fontFamily(FontFamily::Mono)
                            ->extraAttributes(['style' => 'white-space: pre-wrap;']),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
