<?php

namespace Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;

use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;

class ViewAiRequest extends ViewRecord
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->components([
                Components\Section::make('Request Information')
                    ->schema([
                        Components\TextEntry::make('provider')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'openai' => 'success',
                                'anthropic' => 'info',
                                'gemini' => 'warning',
                                'perplexity' => 'danger',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('model'),
                        Components\TextEntry::make('request_type')->label('Request Type')->badge(),
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                'rate_limited' => 'warning',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('http_code')->label('HTTP Code'),
                    ])
                    ->columns(3),

                Components\Section::make('Token Usage')
                    ->schema([
                        Components\TextEntry::make('prompt_tokens')->label('Prompt Tokens')->numeric(),
                        Components\TextEntry::make('completion_tokens')->label('Completion Tokens')->numeric(),
                        Components\TextEntry::make('total_tokens')->label('Total Tokens')->numeric()->weight('bold'),
                    ])
                    ->columns(3),

                Components\Section::make('Cost')
                    ->schema([
                        Components\TextEntry::make('cost_usd')
                            ->label('Cost (USD)')
                            ->formatStateUsing(fn ($state) => $state !== null ? '$' . number_format($state, 4) : '--')
                            ->size('lg')
                            ->weight('bold'),
                        Components\TextEntry::make('meta.cost_source')
                            ->label('Cost Source')
                            ->formatStateUsing(fn ($state) => ucfirst($state ?? 'auto'))
                            ->badge()
                            ->color(fn ($state) => match ($state ?? 'auto') {
                                'auto' => 'success',
                                'manual' => 'warning',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Components\Section::make('Relations')
                    ->schema([
                        Components\TextEntry::make('user.name')->label('User')->default('N/A'),
                        Components\TextEntry::make('tenant_id')->label('Tenant ID')->default('N/A'),
                    ])
                    ->columns(2),

                Components\Section::make('Timeline')
                    ->schema([
                        Components\TextEntry::make('occurred_at')->label('Occurred At')->dateTime('M d, Y H:i:s'),
                        Components\TextEntry::make('created_at')->label('Logged At')->dateTime('M d, Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }
}
