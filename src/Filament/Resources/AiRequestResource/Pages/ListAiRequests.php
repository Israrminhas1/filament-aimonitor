<?php

namespace Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages;

use Filament\Actions\Action;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Services\AiPricingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAiRequests extends ListRecords
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculateMissingCosts')
                ->label('Recalculate missing costs')
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->visible(fn (): bool => AiRequest::query()->missingCost()->exists())
                ->requiresConfirmation()
                ->modalDescription('Price every request that has no cost yet, using the current pricing table.')
                ->action(function (): void {
                    $pricing = app(AiPricingService::class);
                    $updated = 0;
                    $total = 0;

                    AiRequest::query()->missingCost()->chunkById(500, function ($records) use ($pricing, &$updated, &$total) {
                        foreach ($records as $record) {
                            $total++;
                            $updated += (int) $pricing->recalculate($record);
                        }
                    });

                    Notification::make()
                        ->title("Priced {$updated} of {$total} requests")
                        ->body($updated < $total ? 'Add pricing for the remaining models, or a provider default / global fallback.' : null)
                        ->status($updated < $total ? 'warning' : 'success')
                        ->send();
                }),
        ];
    }
}
